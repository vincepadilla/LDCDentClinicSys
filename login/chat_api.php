<?php
// --- CONFIGURATION ---
$geminiApiKey = 'AIzaSyBspIeePTQCxRjjThpDVVk2RrqQ5L72yEo';
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

// Handle preflight request
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit(0);
}

// --- FPDF SETUP --- 
// Since chat_api.php is inside /login, but fpdf is outside
$fpdfPath = __DIR__ . '/../fpdf/fpdf.php'; // Go up one level then into fpdf
$fpdfAvailable = false;

if (file_exists($fpdfPath)) {
    require_once($fpdfPath);
    if (class_exists('FPDF')) {
        $fpdfAvailable = true;
        // Define font path
        if (!defined('FPDF_FONTPATH')) {
            define('FPDF_FONTPATH', __DIR__ . '/../fpdf/font/');
        }
    }
} else {
    error_log("FPDF not found at: " . $fpdfPath);
}

// --- 1. GET DATA FROM FRONTEND ---
$input = json_decode(file_get_contents('php://input'), true);
if (!$input || !isset($input['question']) || !isset($input['context'])) {
    http_response_code(400);
    echo json_encode(['error' => 'Invalid input. Question and context are required.']);
    exit;
}

$adminQuestion = trim($input['question']);
$reportContext = $input['context'];
$conversationHistory = $input['history'] ?? [];

// --- 2. DETECT INTENT ---
$adminQuestionLower = strtolower($adminQuestion);
$isPdfRequest = strpos($adminQuestionLower, 'pdf') !== false || strpos($adminQuestionLower, 'summary') !== false;
$includeChart = strpos($adminQuestionLower, 'chart') !== false;
$isFollowUp = !empty($conversationHistory) && !$isPdfRequest;

// --- 3. GEMINI API CONFIG ---
$geminiApiUrl = 'https://generativelanguage.googleapis.com/v1beta/models/gemini-2.5-flash:generateContent?key=' . $geminiApiKey;
$responsePayload = ['answer' => '', 'history' => []];

// --- 4. BUILD CONVERSATION CONTEXT ---
function buildConversationContext($reportContext, $conversationHistory, $currentQuestion, $isFollowUp) {
    $formattedData = formatReportData($reportContext);
    
    $context = "
DENTAL CLINIC REPORT ANALYST ROLE:
You are a specialized AI assistant for dental clinic analytics. Your role is to analyze report data and provide insights.

AVAILABLE REPORT DATA:
$formattedData

CRITICAL INSTRUCTIONS:
- Be concise but informative
- Focus on business insights from the data
- Use bullet points for lists when appropriate
- Format numbers clearly";

    // Add conversation history for context
    if (!empty($conversationHistory) && $isFollowUp) {
        $context .= "\n\nCONVERSATION HISTORY:\n";
        foreach (array_slice($conversationHistory, -3) as $exchange) {
            $context .= "User: " . $exchange['question'] . "\n";
            $context .= "Assistant: " . $exchange['answer'] . "\n\n";
        }
    }

    $context .= "\nCURRENT QUESTION: " . $currentQuestion;
    
    return $context;
}

function formatReportData($reportContext) {
    $formatted = "";
    
    if (isset($reportContext['total_appointments'])) {
        $formatted .= "CLINIC OVERVIEW:\n";
        $formatted .= "- Total Appointments: " . number_format($reportContext['total_appointments']) . "\n";
        $formatted .= "- Total Revenue: ₱" . number_format($reportContext['total_revenue'] ?? 0, 2) . "\n";
        $formatted .= "- Monthly Appointments: " . number_format($reportContext['monthly_appointments']) . "\n";
        $formatted .= "- Most Popular Service: " . ($reportContext['popular_service'] ?? 'N/A') . "\n\n";
    }
    
    if (isset($reportContext['monthly_service_data'])) {
        $formatted .= "MONTHLY SERVICE DISTRIBUTION:\n";
        foreach ($reportContext['monthly_service_data'] as $month => $data) {
            $monthName = date('F', mktime(0, 0, 0, $month, 1));
            $formatted .= "- $monthName: " . ($data['total'] ?? 0) . " total appointments\n";
        }
        $formatted .= "\n";
    }
    
    return $formatted;
}

// --- 5. HANDLE PDF REQUEST ---
if ($isPdfRequest) {
    // Check if FPDF is available
    if (!$fpdfAvailable) {
        $responsePayload['answer'] = "📄 PDF generation is currently unavailable. The FPDF library is not installed or not found. Please check that the fpdf folder exists in the main directory.";
        $responsePayload['history'] = array_merge($conversationHistory, [
            ['question' => $adminQuestion, 'answer' => $responsePayload['answer']]
        ]);
        echo json_encode($responsePayload);
        exit;
    }

    // Simple PDF content without AI for testing
    $summaryText = "Dental Clinic Report Summary\n\n";
    $summaryText .= "This report provides an overview of the clinic's performance metrics and appointment statistics.\n\n";
    
    if (isset($reportContext['total_appointments'])) {
        $summaryText .= "KEY METRICS:\n";
        $summaryText .= "• Total Appointments: " . number_format($reportContext['total_appointments']) . "\n";
        $summaryText .= "• Total Revenue: ₱" . number_format($reportContext['total_revenue'] ?? 0, 2) . "\n";
        $summaryText .= "• This Month Appointments: " . number_format($reportContext['monthly_appointments']) . "\n";
        $summaryText .= "• Most Popular Service: " . ($reportContext['popular_service'] ?? 'N/A') . "\n\n";
    }

    try {
        // Create PDF
        $pdf = new FPDF();
        $pdf->AddPage();

        // Title
        $pdf->SetFont('Arial', 'B', 16);
        $pdf->Cell(0, 10, 'Dental Clinic Report Summary', 0, 1, 'C');
        $pdf->Ln(5);

        // Date
        $pdf->SetFont('Arial', 'I', 10);
        $pdf->Cell(0, 6, 'Generated on: ' . date('F j, Y g:i A'), 0, 1, 'C');
        $pdf->Ln(10);

        // Content
        $pdf->SetFont('Arial', '', 11);
        
        // Split the summary text into lines and add to PDF
        $lines = explode("\n", $summaryText);
        foreach ($lines as $line) {
            if (strpos($line, '•') === 0) {
                $pdf->SetFont('Arial', 'B', 11);
                $pdf->Cell(5); // Indent bullet points
                $pdf->MultiCell(0, 6, $line);
            } else {
                $pdf->SetFont('Arial', '', 11);
                $pdf->MultiCell(0, 6, $line);
            }
        }

        // Add monthly data if available
        if (isset($reportContext['monthly_service_data'])) {
            $pdf->Ln(8);
            $pdf->SetFont('Arial', 'B', 12);
            $pdf->Cell(0, 8, 'Monthly Appointment Summary:', 0, 1);
            $pdf->SetFont('Arial', '', 10);
            
            foreach ($reportContext['monthly_service_data'] as $month => $data) {
                if (($data['total'] ?? 0) > 0) {
                    $monthName = date('F', mktime(0, 0, 0, $month, 1));
                    $pdf->Cell(10); // Indent
                    $pdf->Cell(0, 6, "- $monthName: " . ($data['total'] ?? 0) . " appointments", 0, 1);
                }
            }
        }

        // Footer
        $pdf->Ln(15);
        $pdf->SetFont('Arial', 'I', 8);
        $pdf->Cell(0, 6, 'Generated by Dental Clinic Management System', 0, 1, 'C');

        // Create reports directory if it doesn't exist (outside login folder)
        $dir = __DIR__ . '/../reports/'; // Go up one level then into reports
        if (!file_exists($dir)) {
            if (!mkdir($dir, 0755, true)) {
                throw new Exception('Could not create reports directory. Check folder permissions.');
            }
        }

        // Generate filename and save
        $filename = 'clinic_report_' . date('Y-m-d_His') . '.pdf';
        $fullPath = $dir . $filename;
        
        $pdf->Output('F', $fullPath);

        // Verify file was created
        if (!file_exists($fullPath)) {
            throw new Exception('PDF file was not created. Check write permissions.');
        }

        // Check file size to ensure it's not empty
        if (filesize($fullPath) === 0) {
            throw new Exception('PDF file was created but is empty.');
        }

        // Success response - note the path for web access
        $responsePayload['answer'] = "✅ PDF report generated successfully! You can download it using the link below.";
        $responsePayload['pdf_url'] = '../reports/' . $filename; // Path for web access
        
    } catch (Exception $e) {
        error_log("PDF Generation Error: " . $e->getMessage());
        $responsePayload['answer'] = "❌ PDF generation failed: " . $e->getMessage();
        $responsePayload['pdf_url'] = null;
    }
    
    // Add to conversation history
    $responsePayload['history'] = array_merge($conversationHistory, [
        [
            'question' => $adminQuestion,
            'answer' => $responsePayload['answer']
        ]
    ]);

} else {
    // --- 6. HANDLE NORMAL QUESTIONS ---
    $prompt = buildConversationContext($reportContext, $conversationHistory, $adminQuestion, $isFollowUp);
    
    $answer = callGemini($geminiApiUrl, $prompt);
    
    $answer = trim($answer);
    
    // Add to conversation history
    $newHistory = array_merge($conversationHistory, [
        ['question' => $adminQuestion, 'answer' => $answer]
    ]);
    
    // Keep only last 8 exchanges
    if (count($newHistory) > 8) {
        $newHistory = array_slice($newHistory, -8);
    }
    
    $responsePayload['answer'] = $answer;
    $responsePayload['history'] = $newHistory;
}

// --- 7. SEND RESPONSE ---
echo json_encode($responsePayload);

/**
 * Gemini API call helper
 */
function callGemini($url, $prompt) {
    $payload = json_encode([
        'contents' => [['parts' => [['text' => $prompt]]]],
        'generationConfig' => [
            'temperature' => 0.3,
            'maxOutputTokens' => 1000,
        ]
    ]);

    $ch = curl_init($url);
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_POST => true,
        CURLOPT_POSTFIELDS => $payload,
        CURLOPT_HTTPHEADER => [
            'Content-Type: application/json',
            'Content-Length: ' . strlen($payload)
        ],
        CURLOPT_TIMEOUT => 30,
    ]);

    $response = curl_exec($ch);
    
    if (curl_errno($ch)) {
        $error = curl_error($ch);
        curl_close($ch);
        return "I apologize, but I'm experiencing technical difficulties. Please try again.";
    }
    curl_close($ch);

    $responseData = json_decode($response, true);
    if (isset($responseData['candidates'][0]['content']['parts'][0]['text'])) {
        return $responseData['candidates'][0]['content']['parts'][0]['text'];
    } else {
        return "I apologize, but I'm having trouble processing your request right now. Please try again.";
    }
}
?>