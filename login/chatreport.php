<?php
require_once 'config.php'; 

// Get report data for the AI context
function getReportData($con) {
    $reportData = [];
    
    // Total Appointments
    $totalAppointments = mysqli_fetch_assoc(mysqli_query($con, "SELECT COUNT(*) as total FROM appointments"))['total'];
    
    // Total Revenue
    $totalRevenue = mysqli_fetch_assoc(mysqli_query($con, "SELECT SUM(amount) as total FROM payment WHERE status = 'paid'"))['total'] ?? 0;
    
    // Monthly Appointments
    $monthlyAppointments = mysqli_fetch_assoc(mysqli_query($con, 
        "SELECT COUNT(*) as total FROM appointments WHERE MONTH(appointment_date) = MONTH(CURRENT_DATE()) 
         AND YEAR(appointment_date) = YEAR(CURRENT_DATE())"))['total'];
    
    // Popular Service
    $popularService = mysqli_fetch_assoc(mysqli_query($con, 
        "SELECT s.service_category, COUNT(*) as count 
         FROM appointments a 
         LEFT JOIN services s ON a.service_id = s.service_id 
         GROUP BY s.service_category 
         ORDER BY count DESC LIMIT 1"));
    
    // Monthly Service Data
    $monthlyServiceData = [];
    $currentYear = date('Y');
    
    for ($month = 1; $month <= 12; $month++) {
        $sql = "SELECT s.service_category, COUNT(*) AS count
                FROM appointments a
                LEFT JOIN services s ON a.service_id = s.service_id
                WHERE MONTH(a.appointment_date) = $month AND YEAR(a.appointment_date) = $currentYear
                GROUP BY s.service_category";
        
        $result = mysqli_query($con, $sql);
        $services = [];
        $counts = [];
        $total = 0;
        
        while ($row = mysqli_fetch_assoc($result)) {
            $services[] = $row['service_category'];
            $counts[] = (int)$row['count'];
            $total += (int)$row['count'];
        }
        
        $monthlyServiceData[$month] = [
            'labels' => $services,
            'counts' => $counts,
            'total' => $total
        ];
    }
    
    // Recent Appointments (Last 30 days)
    $sql = "SELECT appointment_date, COUNT(*) as count FROM appointments 
            WHERE appointment_date >= DATE_SUB(CURDATE(), INTERVAL 30 DAY)
            GROUP BY appointment_date ORDER BY appointment_date";
    $result = mysqli_query($con, $sql);
    $dates = [];
    $counts = [];
    
    while ($row = mysqli_fetch_assoc($result)) {
        $dates[] = date('M j', strtotime($row['appointment_date']));
        $counts[] = (int)$row['count'];
    }
    
    // Financial Data (Last 30 days)
    $sql = "SELECT a.appointment_date, SUM(p.amount) as total_amount
            FROM payment p
            INNER JOIN appointments a ON p.appointment_id = a.appointment_id
            WHERE p.status = 'paid' AND a.appointment_date >= DATE_SUB(CURDATE(), INTERVAL 30 DAY)
            GROUP BY a.appointment_date
            ORDER BY a.appointment_date";
    $result = mysqli_query($con, $sql);
    $datesPaid = [];
    $amountsPaid = [];
    
    while ($row = mysqli_fetch_assoc($result)) {
        $datesPaid[] = date('M j', strtotime($row['appointment_date']));
        $amountsPaid[] = (float)$row['total_amount'];
    }
    
    $reportData = [
        'total_appointments' => $totalAppointments,
        'total_revenue' => $totalRevenue,
        'monthly_appointments' => $monthlyAppointments,
        'popular_service' => $popularService['service_category'] ?? 'N/A',
        'monthly_service_data' => $monthlyServiceData,
        'recent_appointments' => [
            'dates' => $dates,
            'counts' => $counts
        ],
        'financial_data' => [
            'dates' => $datesPaid,
            'amounts' => $amountsPaid
        ]
    ];
    
    return $reportData;
}

$reportContext = getReportData($con);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Report Analyst AI</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        .chatbot-container {
            position: fixed;
            bottom: 20px;
            right: 20px;
            z-index: 1000;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }
        
        .chatbot-icon {
            width: 60px;
            height: 60px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.2);
            transition: all 0.3s ease;
            animation: pulse 2s infinite;
        }
        
        .chatbot-icon:hover {
            transform: scale(1.1);
            box-shadow: 0 6px 20px rgba(0, 0, 0, 0.3);
        }
        
        .chatbot-icon i {
            color: white;
            font-size: 24px;
        }
        
        .chat-window {
            position: absolute;
            bottom: 70px;
            right: 0;
            width: 350px;
            height: 500px;
            background: white;
            border-radius: 15px;
            box-shadow: 0 5px 25px rgba(0, 0, 0, 0.2);
            display: none;
            flex-direction: column;
            overflow: hidden;
        }
        
        .chat-header {
            background: #48A6A7;
            color: white;
            padding: 15px;
            display: flex;
            align-items: center;
            justify-content: space-between;
        }
        
        .chat-header h3 {
            margin: 0;
            font-size: 16px;
            display: flex;
            align-items: center;
            gap: 8px;
        }
        
        .close-btn {
            background: none;
            border: none;
            color: white;
            cursor: pointer;
            font-size: 18px;
            padding: 5px;
        }
        
        .chat-messages {
            flex: 1;
            padding: 15px;
            overflow-y: auto;
            background: #f8f9fa;
        }
        
        .message {
            margin-bottom: 15px;
            padding: 10px 15px;
            border-radius: 18px;
            max-width: 85%;
            word-wrap: break-word;
        }
        
        .user-message {
            background: #357f80ff;
            color: white;
            margin-left: auto;
            border-bottom-right-radius: 5px;
        }
        
        .ai-message {
            background: white;
            color: #333;
            border: 1px solid #e0e0e0;
            border-bottom-left-radius: 5px;
        }
        
        .chat-input {
            padding: 15px;
            border-top: 1px solid #e0e0e0;
            background: white;
            display: flex;
            gap: 10px;
        }
        
        .chat-input input {
            flex: 1;
            padding: 10px 15px;
            border: 1px solid #ddd;
            border-radius: 25px;
            outline: none;
            font-size: 14px;
        }
        
        .chat-input input:focus {
            border-color: #667eea;
        }
        
        .send-btn {
            background: #48A6A7;
            color: white;
            border: none;
            border-radius: 50%;
            width: 40px;
            height: 40px;
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
            transition: background 0.3s ease;
        }
        
        .send-btn:hover {
            background: #57bcbeff;
        }
        
        .quick-actions {
            padding: 10px 15px;
            background: white;
            border-bottom: 1px solid #e0e0e0;
            display: flex;
            flex-wrap: wrap;
            gap: 8px;
        }
        
        .quick-btn {
            background: #f8f9fa;
            border: 1px solid #e0e0e0;
            border-radius: 15px;
            padding: 6px 12px;
            font-size: 12px;
            cursor: pointer;
            transition: all 0.3s ease;
        }
        
        .quick-btn:hover {
            background: #667eea;
            color: white;
            border-color: #667eea;
        }
        
        .pdf-download {
            padding: 10px 15px;
            background: #e7f3ff;
            border-top: 1px solid #cce5ff;
            display: none;
            text-align: center;
        }
        
        .pdf-link {
            color: #007bff;
            text-decoration: none;
            font-weight: 500;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
        }
        
        .pdf-link:hover {
            text-decoration: underline;
        }
        
        .typing-indicator {
            display: inline-block;
            padding: 10px 15px;
            background: white;
            border: 1px solid #e0e0e0;
            border-radius: 18px;
            border-bottom-left-radius: 5px;
            color: #666;
            font-style: italic;
        }
        
        
    </style>
</head>
<body>
    <div class="chatbot-container">
        <div class="chatbot-icon" id="chatbotIcon">
            <i class="fas fa-robot"></i>
        </div>
        
        <div class="chat-window" id="chatWindow">
            <div class="chat-header">
                <h3><i class="fas fa-robot"></i> Report Analyst AI</h3>
                <button class="close-btn" id="closeChat"><i class="fas fa-times"></i></button>
            </div>
            
            <div class="quick-actions">
                <button class="quick-btn" onclick="askQuickQuestion('What are the key insights from the reports?')">
                    <i class="fas fa-chart-line"></i> Get Insights
                </button>
                <button class="quick-btn" onclick="askQuickQuestion('Generate PDF summary')">
                    <i class="fas fa-file-pdf"></i> PDF Report
                </button>
                <button class="quick-btn" onclick="askQuickQuestion('Show me trends and patterns')">
                    <i class="fas fa-chart-bar"></i> Analyze Trends
                </button>
            </div>
            
            <div class="chat-messages" id="chatMessages">
                <div class="message ai-message">
                    <strong>AI:</strong> Hello! I'm your Report Analyst AI. I can help you analyze clinic reports, provide insights, and generate PDF summaries. How can I assist you today?
                </div>
            </div>
            
            <div class="pdf-download" id="pdfDownload">
                <a href="#" class="pdf-link" id="pdfLink" target="_blank">
                    <i class="fas fa-download"></i> Download PDF Report
                </a>
            </div>
            
            <div class="chat-input">
                <input type="text" id="aiQuestion" placeholder="Ask about your reports..." onkeypress="handleKeyPress(event)">
                <button class="send-btn" onclick="askAI()">
                    <i class="fas fa-paper-plane"></i>
                </button>
            </div>
        </div>
    </div>

    <script>
        // Report data from PHP
        const reportContext = <?php echo json_encode($reportContext); ?>;
        let conversationHistory = [];
        let isChatOpen = false;
        
        // DOM Elements
        const chatbotIcon = document.getElementById('chatbotIcon');
        const chatWindow = document.getElementById('chatWindow');
        const chatMessages = document.getElementById('chatMessages');
        const closeChat = document.getElementById('closeChat');
        const aiQuestion = document.getElementById('aiQuestion');
        const pdfDownload = document.getElementById('pdfDownload');
        const pdfLink = document.getElementById('pdfLink');
        
        // Toggle chat window
        chatbotIcon.addEventListener('click', () => {
            isChatOpen = !isChatOpen;
            chatWindow.style.display = isChatOpen ? 'flex' : 'none';
            if (isChatOpen) {
                aiQuestion.focus();
            }
        });
        
        // Close chat
        closeChat.addEventListener('click', () => {
            isChatOpen = false;
            chatWindow.style.display = 'none';
        });
        
        // Handle Enter key
        function handleKeyPress(event) {
            if (event.key === 'Enter') {
                askAI();
            }
        }
        
        // Quick question buttons
        function askQuickQuestion(question) {
            aiQuestion.value = question;
            askAI();
        }
        
        // Main AI function
        async function askAI() {
            const question = aiQuestion.value.trim();
            if (!question) return;
            
            // Add user message to chat
            addMessageToChat('You', question, 'user-message');
            aiQuestion.value = '';
            
            // Show typing indicator
            const typingIndicator = document.createElement('div');
            typingIndicator.className = 'message ai-message';
            typingIndicator.innerHTML = '<div class="typing-indicator">AI is thinking...</div>';
            chatMessages.appendChild(typingIndicator);
            chatMessages.scrollTop = chatMessages.scrollHeight;
            
            try {
                const response = await fetch('chat_api.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                    },
                    body: JSON.stringify({
                        question: question,
                        context: reportContext,
                        history: conversationHistory
                    })
                });
                
                const data = await response.json();
                
                // Remove typing indicator
                typingIndicator.remove();
                
                if (data.error) {
                    addMessageToChat('AI', 'Sorry, I encountered an error: ' + data.error, 'ai-message');
                } else {
                    addMessageToChat('AI', data.answer, 'ai-message');
                    conversationHistory = data.history || [];
                    
                    // Handle PDF download
                    if (data.pdf_url) {
                        pdfDownload.style.display = 'block';
                        pdfLink.href = data.pdf_url;
                        pdfLink.download = data.pdf_url.split('/').pop();
                    }
                }
            } catch (error) {
                typingIndicator.remove();
                addMessageToChat('AI', 'Sorry, I encountered a network error. Please try again.', 'ai-message');
                console.error('AI Chat Error:', error);
            }
        }
        
        // Add message to chat
        function addMessageToChat(sender, message, className) {
            const messageDiv = document.createElement('div');
            messageDiv.className = `message ${className}`;
            
            if (className === 'user-message') {
                messageDiv.innerHTML = `<strong>${sender}:</strong> ${message}`;
            } else {
                messageDiv.innerHTML = `<strong>${sender}:</strong> ${message}`;
            }
            
            chatMessages.appendChild(messageDiv);
            chatMessages.scrollTop = chatMessages.scrollHeight;
        }
        
        // Auto-focus input when chat opens
        chatbotIcon.addEventListener('click', () => {
            setTimeout(() => aiQuestion.focus(), 100);
        });
    </script>
</body>
</html>