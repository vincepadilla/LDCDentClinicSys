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
$fpdfPath = __DIR__ . '/../fpdf/fpdf.php';
$fpdfAvailable = false;

if (file_exists($fpdfPath)) {
    require_once($fpdfPath);
    if (class_exists('FPDF')) {
        $fpdfAvailable = true;
        if (!defined('FPDF_FONTPATH')) {
            define('FPDF_FONTPATH', __DIR__ . '/../fpdf/font/');
        }
    }
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
$isPdfRequest = strpos($adminQuestionLower, 'pdf') !== false || 
                strpos($adminQuestionLower, 'summary') !== false ||
                strpos($adminQuestionLower, 'download') !== false;
$isGreeting = in_array($adminQuestionLower, ['hi', 'hello', 'hey', 'good morning', 'good afternoon', 'good evening']);

// --- 3. HANDLE SIMPLE GREETINGS WITH FOLLOW-UP ---
if ($isGreeting) {
    $greetingResponse = "Hello! I'm your Report Analyst AI. I can help you analyze clinic reports and generate insights. ";
    $greetingResponse .= "Would you like me to show you today's key metrics, generate a report, or analyze specific trends?";
    
    $responsePayload = [
        'answer' => $greetingResponse,
        'history' => array_merge($conversationHistory, [
            ['question' => $adminQuestion, 'answer' => $greetingResponse]
        ])
    ];
    echo json_encode($responsePayload);
    exit;
}

// --- 4. CONTINUE WITH NORMAL PROCESSING FOR OTHER QUESTIONS ---
$geminiApiUrl = 'https://generativelanguage.googleapis.com/v1beta/models/gemini-2.5-flash:generateContent?key=' . $geminiApiKey;
$responsePayload = ['answer' => '', 'history' => []];

// Build conversation context function
function buildConversationContext($reportContext, $conversationHistory, $currentQuestion) {
    $formattedData = formatReportData($reportContext);
    
    $context = "You are a dental clinic report analyst. Analyze this data and provide clear, helpful responses without using markdown, asterisks, or icons.

IMPORTANT: Respond in plain text only. No formatting, no icons, no asterisks.

AVAILABLE DATA:
$formattedData

QUESTION: $currentQuestion

Provide a straightforward answer focused on the data analysis. Use simple language and be helpful.";
    
    return $context;
}

function formatReportData($reportContext) {
    $formatted = "";
    
    if (isset($reportContext['total_appointments'])) {
        $formatted .= "CLINIC OVERVIEW:\n";
        $formatted .= "Total Appointments: " . number_format($reportContext['total_appointments']) . "\n";
        $formatted .= "Total Revenue: ₱" . number_format($reportContext['total_revenue'] ?? 0, 2) . "\n";
        $formatted .= "Current Month Appointments: " . number_format($reportContext['monthly_appointments']) . "\n";
        $formatted .= "Today's Appointments: " . number_format($reportContext['today_appointments']) . "\n";
        $formatted .= "Most Popular Service: " . ($reportContext['popular_service'] ?? 'N/A') . "\n";
        $formatted .= "No-Show Rate: " . ($reportContext['no_show_rate'] ?? '0') . "%\n\n";
    }
    
    if (isset($reportContext['appointment_statuses'])) {
        $formatted .= "APPOINTMENT STATUS:\n";
        foreach ($reportContext['appointment_statuses'] as $status => $count) {
            $percentage = $reportContext['total_appointments'] > 0 ? 
                round(($count / $reportContext['total_appointments']) * 100, 1) : 0;
            $formatted .= ucfirst($status) . ": $count ($percentage%)\n";
        }
        $formatted .= "\n";
    }
    
    if (isset($reportContext['monthly_service_data'])) {
        $formatted .= "MONTHLY PERFORMANCE:\n";
        $currentMonth = date('n');
        foreach ($reportContext['monthly_service_data'] as $month => $data) {
            if (($data['total'] ?? 0) > 0) {
                $monthName = date('F', mktime(0, 0, 0, $month, 1));
                $currentIndicator = ($month == $currentMonth) ? " (Current)" : "";
                $formatted .= "$monthName: " . $data['total'] . " appointments$currentIndicator\n";
            }
        }
    }
    
    return $formatted;
}

// Handle PDF requests
if ($isPdfRequest) {
    if (!$fpdfAvailable) {
        $responsePayload['answer'] = "PDF generation is currently unavailable. Please contact system administrator.";
    } else {
        try {
            $pdf = new FPDF();
            $pdf->AddPage();
            $pdf->SetFont('Arial', 'B', 16);
            $pdf->Cell(0, 10, 'Dental Clinic Report', 0, 1, 'C');
            $pdf->Ln(10);
            $pdf->SetFont('Arial', '', 12);
            $pdf->MultiCell(0, 8, 'Clinic Report Summary generated on ' . date('F j, Y'));
            
            // Add basic metrics
            if (isset($reportContext['total_appointments'])) {
                $pdf->Ln(5);
                $pdf->SetFont('Arial', 'B', 14);
                $pdf->Cell(0, 10, 'Key Metrics:', 0, 1);
                $pdf->SetFont('Arial', '', 12);
                $pdf->Cell(0, 8, 'Total Appointments: ' . number_format($reportContext['total_appointments']), 0, 1);
                $pdf->Cell(0, 8, 'Total Revenue: ₱' . number_format($reportContext['total_revenue'] ?? 0, 2), 0, 1);
                $pdf->Cell(0, 8, 'Current Month: ' . number_format($reportContext['monthly_appointments']) . ' appointments', 0, 1);
            }
            
            $dir = __DIR__ . '/../reports/';
            if (!file_exists($dir)) {
                mkdir($dir, 0755, true);
            }
            
            $filename = 'clinic_report_' . date('Y-m-d_His') . '.pdf';
            $fullPath = $dir . $filename;
            $pdf->Output('F', $fullPath);
            
            $responsePayload['answer'] = "PDF report has been generated successfully. You can download it using the link below. Would you like me to analyze any specific aspect of the data while you review the report?";
            $responsePayload['pdf_url'] = '../reports/' . $filename;
            
        } catch (Exception $e) {
            $responsePayload['answer'] = "PDF generation failed: " . $e->getMessage();
        }
    }
} else {
    // Handle normal questions with Gemini
    $prompt = buildConversationContext($reportContext, $conversationHistory, $adminQuestion);
    $answer = callGemini($geminiApiUrl, $prompt);
    
    // Clean up the response - remove any remaining formatting
    $answer = cleanResponse($answer);
    
    // Add follow-up question for better conversation flow
    $answer = addFollowUpQuestion($answer, $adminQuestionLower);
    
    $responsePayload['answer'] = trim($answer);
    $responsePayload['history'] = array_merge($conversationHistory, [
        ['question' => $adminQuestion, 'answer' => $answer]
    ]);
}

// Send response
echo json_encode($responsePayload);

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

function cleanResponse($answer) {
    // Remove asterisks and other formatting
    $answer = str_replace(['**', '*', '•', '📊', '📈', '🎯', '✅', '❌', '⚠️', '🤔'], '', $answer);
    
    // Remove multiple spaces
    $answer = preg_replace('/\s+/', ' ', $answer);
    
    // Ensure proper sentence structure
    $answer = trim($answer);
    
    return $answer;
}

function addFollowUpQuestion($answer, $userQuestion) {
    $userQuestion = strtolower($userQuestion);
    
    // Define follow-up questions based on context
    $followUps = [
        'trend' => " Would you like me to analyze any specific time period or compare with previous data?",
        'revenue' => " Should I break down the revenue by service type or analyze profitability trends?",
        'appointment' => " Would you like to see the appointment distribution by service or analyze no-show patterns?",
        'service' => " Do you want me to compare service performance or identify growth opportunities?",
        'report' => " Is there any specific aspect of the data you'd like me to focus on?",
        'default' => " Is there anything else you'd like to know about the clinic data?"
    ];
    
    // Determine which follow-up to use
    $followUp = $followUps['default'];
    
    if (strpos($userQuestion, 'trend') !== false || strpos($userQuestion, 'pattern') !== false) {
        $followUp = $followUps['trend'];
    } elseif (strpos($userQuestion, 'revenue') !== false || strpos($userQuestion, 'profit') !== false || strpos($userQuestion, 'financial') !== false) {
        $followUp = $followUps['revenue'];
    } elseif (strpos($userQuestion, 'appointment') !== false || strpos($userQuestion, 'schedule') !== false) {
        $followUp = $followUps['appointment'];
    } elseif (strpos($userQuestion, 'service') !== false || strpos($userQuestion, 'treatment') !== false) {
        $followUp = $followUps['service'];
    } elseif (strpos($userQuestion, 'report') !== false || strpos($userQuestion, 'data') !== false) {
        $followUp = $followUps['report'];
    }
    
    // Add follow-up if the answer doesn't already end with a question
    if (!preg_match('/[?]$/', trim($answer))) {
        $answer .= $followUp;
    }
    
    return $answer;
}
?>