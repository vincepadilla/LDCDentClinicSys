<?php
include('./login/config.php');
define("TITLE", "Blogs");
include_once('header.php');

$today = date('Y-m-d');
$checkQuery = "SELECT * FROM dental_blogs WHERE DATE(published_at) = '$today'";
$result = mysqli_query($con, $checkQuery);

if (mysqli_num_rows($result) == 0) {
    $apiKey = "AIzaSyBspIeePTQCxRjjThpDVVk2RrqQ5L72yEo";
    $url = "https://generativelanguage.googleapis.com/v1beta/models/gemini-2.5-flash:generateContent?key=$apiKey";
    $prompt = "Generate a short, friendly, and informative blog post for a modern dental clinic website.
Include:
- A catchy title
- Around 150 words of content about dental care, oral hygiene, or smile tips.
Format output like this:
Title: [title]
Content: [text]";

    $data = [
        "contents" => [
            [
                "parts" => [
                    ["text" => $prompt]
                ]
            ]
        ],
    ];

    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, ["Content-Type: application/json"]);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
    $response = curl_exec($ch);
    curl_close($ch);

    $responseData = json_decode($response, true);
    $generatedText = '';

    // Show API error if any
    if (isset($responseData['error'])) {
        echo "<pre>API Error: " . $responseData['error']['message'] . "</pre>";
    }

    // Collect text parts
    if (isset($responseData['candidates'][0]['content']['parts'])) {
        foreach ($responseData['candidates'][0]['content']['parts'] as $part) {
            if (isset($part['text'])) {
                $generatedText .= $part['text'] . "\n";
            }
        }
    }

    // Debug output (optional)
    //echo "<pre>";
    //print_r($responseData);
    //echo "</pre>";

    if (preg_match('/Title:\s*(.*?)\nContent:\s*(.*)/s', $generatedText, $matches)) {
        $title = trim($matches[1]);
        $content = trim($matches[2]);
    } else {
        // fallback if response not formatted correctly
        $title = "Dental Health Tip of the Day";
        $content = $generatedText ?: "Keep your smile healthy by brushing twice a day and visiting your dentist regularly!";
    }

        $idQuery = mysqli_query($con, "SELECT blog_id FROM dental_blogs ORDER BY blog_id DESC LIMIT 1");
    if (mysqli_num_rows($idQuery) > 0) {
        $lastId = mysqli_fetch_assoc($idQuery)['blog_id'];
        $num = (int)substr($lastId, 1) + 1; // remove 'B' and increment
        $newId = "B" . str_pad($num, 3, "0", STR_PAD_LEFT);
    } else {
        $newId = "B001";
    }

    $stmt = mysqli_prepare($con, "INSERT INTO dental_blogs (title, content, published_at, status) VALUES (?, ?, NOW(), 'published')");
    if ($stmt) {
        mysqli_stmt_bind_param($stmt, "ss", $title, $content);
        mysqli_stmt_execute($stmt);
        mysqli_stmt_close($stmt);
    }

    // keep only 10 recent blogs
    $countQuery = mysqli_query($con, "SELECT COUNT(*) AS total FROM dental_blogs");
    $total = mysqli_fetch_assoc($countQuery)['total'];
    if ($total > 10) {
        $deleteCount = $total - 10;
        mysqli_query($con, "DELETE FROM dental_blogs ORDER BY published_at ASC LIMIT $deleteCount");
    }
}

// Fetch blogs
$blogs = mysqli_query($con, "SELECT * FROM dental_blogs ORDER BY published_at DESC");
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Dental Clinic Blog</title>
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600&display=swap');

        html {
            scroll-behavior: smooth;
        }

        body {
            font-family: 'Poppins', sans-serif;
            background: #f3f4f6;
            margin: 0;
        }

        .title h1 {
            text-align: center;
            font-size: 2.5rem;
            margin-top: 30px;
        }

        .title p {
            text-align: center;
            font-size: 1.2rem;
        }

        .blog-container {
            max-width: 900px;
            margin: auto;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 25px;
            padding: 0 20px;
        }

        .blog-card {
            background: #fff;
            border-radius: 16px;
            box-shadow: 0 4px 10px rgba(0,0,0,0.1);
            overflow: hidden;
            transition: transform 0.3s ease, box-shadow 0.3s ease;
            margin-top: 30px;
            margin-bottom: 15px;
            border: 1px solid black;
        }

        .blog-card:hover {
            transform: translateY(-6px);
            box-shadow: 0 6px 20px rgba(0,0,0,0.15);
        }

        .blog-content {
            padding: 20px 25px;
        }

        .blog-content h2 {
            color: #5e35b1;
            font-size: 1.25rem;
            margin-bottom: 10px;
        }

        .blog-content p {
            color: #555;
            line-height: 1.6;
            margin-bottom: 15px;
        }

        .blog-footer {
            display: flex;
            justify-content: space-between;
            align-items: center;
            border-top: 1px solid #eee;
            padding: 12px 25px;
            color: #7a7a7a;
            font-size: 0.9rem;
        }

        .read-more {
            color: #5e35b1;
            text-decoration: none;
            font-weight: 500;
            cursor: pointer;
        }

        .read-more:hover {
            text-decoration: underline;
        }

        .modal {
            display: none;
            position: fixed;
            z-index: 999;
            left: 0;
            top: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0,0,0,0.6);
            justify-content: center;
            align-items: center;
        }

        .modal-content {
            background-color: #fff;
            padding: 30px;
            border-radius: 12px;
            max-width: 600px;
            width: 90%;
            max-height: 80vh;
            overflow-y: auto;
            box-shadow: 0 5px 25px rgba(0,0,0,0.2);
        }

        .modal h2 {
            color: #5e35b1;
            margin-bottom: 15px;
        }

        .close {
            color: #5e35b1;
            float: right;
            font-size: 22px;
            font-weight: bold;
            cursor: pointer;
        }

        .close:hover {
            color: #333;
        }
    </style>
</head>
<body>
    <div class="title">
        <h1>Blogs</h1>
        <p>dental care, oral hygiene, or smile tips.</p>
    </div>

    <div class="blog-container">
        <?php while ($row = mysqli_fetch_assoc($blogs)): ?>
            <div class="blog-card">
                <div class="blog-content">
                    <h2><?= htmlspecialchars($row['title']) ?></h2>
                    <p><?= htmlspecialchars(substr(strip_tags($row['content']), 0, 120)) ?>...</p>
                </div>
                <div class="blog-footer">
                    <span><?= date("F d, Y", strtotime($row['published_at'])) ?></span>
                    <a href="#"
                       class="read-more"
                       data-title="<?= htmlspecialchars($row['title']) ?>"
                       data-content="<?= htmlspecialchars($row['content']) ?>">Read More →</a>
                </div>
            </div>
        <?php endwhile; ?>
    </div>

    <div id="blogModal" class="modal">
        <div class="modal-content">
            <span class="close">&times;</span>
            <h2 id="modalTitle"></h2>
            <p id="modalContent"></p>
        </div>
    </div>

    <script>
        const modal = document.getElementById("blogModal");
        const modalTitle = document.getElementById("modalTitle");
        const modalContent = document.getElementById("modalContent");
        const closeModal = document.querySelector(".close");

        document.querySelectorAll(".read-more").forEach(link => {
            link.addEventListener("click", (e) => {
                e.preventDefault();
                modalTitle.textContent = link.getAttribute("data-title");
                modalContent.textContent = link.getAttribute("data-content");
                modal.style.display = "flex";
            });
        });

        closeModal.onclick = () => modal.style.display = "none";
        window.onclick = (event) => {
            if (event.target === modal) modal.style.display = "none";
        };
    </script>

    <?php include_once('footer.php'); ?>
</body>
</html>

<?php mysqli_close($con); ?>
