<?php
/*
Copyright © 2024 NA7KR Kevin Roberts. All rights reserved.

Licensed under the Apache License, Version 2.0 (the "License");
you may not use this file except in compliance with the License.
You may obtain a copy of the License at

    http://www.apache.org/licenses/LICENSE-2.0

Unless required by applicable law or agreed to in writing, software
distributed under the License is distributed on an "AS IS" BASIS,
WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
See the License for the specific language governing permissions and
limitations under the License.
 */
session_start();

$root = realpath($_SERVER["DOCUMENT_ROOT"]);
$title = "Money";
$config = include($root . '/config.php');
include("$root/backend/header.php");

// Check login
if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true) {
    header('Location: login.php');
    exit;
}


ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

$file = $_GET['file'] ?? null;

if ($file) {
    $filePath = realpath($file); // Ensure secure handling of the file
    if ($filePath && file_exists($filePath)) {
        $fileType = mime_content_type($filePath);

        // Debugging information
        echo "<!-- File Path: $filePath -->";
        echo "<!-- File Type: $fileType -->";

        // Check if it's a supported file type (PDF or JPG/PNG)
        if (in_array($fileType, ['application/pdf', 'image/jpeg', 'image/png'])) {
            // Convert file path to URL
            $fileUrl = str_replace($_SERVER['DOCUMENT_ROOT'], '', $filePath);
            ?>
            <!DOCTYPE html>
            <html lang="en">
            <head>
                <meta charset="UTF-8">
                <meta name="viewport" content="width=device-width, initial-scale=1.0">
                <title>View File</title>
                <script src="https://cdnjs.cloudflare.com/ajax/libs/pdf.js/2.10.377/pdf.min.js"></script> <!-- For PDF.js -->
                <style>
                    body {
                        margin: 0;
                        padding: 0;
                        display: flex;
                        flex-direction: column;
                        height: 100vh;
                    }

                    #toolbar {
                        position: sticky;
                        top: 0;
                        display: flex;
                        justify-content: space-between;
                        align-items: center;
                        padding: 10px;
                        background-color: #f0f0f0;
                        border-bottom: 1px solid #ccc;
                        z-index: 1000;
                    }

                    #toolbar button {
                        padding: 8px 12px;
                        margin: 0 5px;
                        border: none;
                        background-color: #007bff;
                        color: white;
                        font-size: 14px;
                        cursor: pointer;
                        border-radius: 4px;
                    }

                    #toolbar button:hover {
                        background-color: #0056b3;
                    }

                    #viewerContainer {
                        overflow: auto;
                        flex: 1;
                        display: flex;
                        justify-content: center;
                        align-items: center;
                        background-color: #f9f9f9;
                    }

                    canvas, img {
                        display: block;
                        max-width: 100%;
                        max-height: 100%;
                        transform-origin: center center;
                    }
                </style>
            </head>
            <body>
                <div id="toolbar">
                    <button id="rotate-btn">Rotate</button>
                    <button onclick="window.print()">Print</button>
                </div>
                <div id="viewerContainer">
                    <?php if ($fileType === 'application/pdf'): ?>
                        <canvas id="pdf-canvas"></canvas>
                        <script>
                            const url = '<?= htmlspecialchars($fileUrl) ?>';
                            const canvas = document.getElementById('pdf-canvas');
                            const ctx = canvas.getContext('2d');
                            let pdfDoc = null, pageNum = 1, rotation = 0;

                            pdfjsLib.getDocument(url).promise.then(doc => {
                                pdfDoc = doc;
                                renderPage(pageNum);
                            });

                            function renderPage(num) {
                                pdfDoc.getPage(num).then(page => {
                                    const viewport = page.getViewport({ scale: 1, rotation });
                                    canvas.width = viewport.width;
                                    canvas.height = viewport.height;

                                    const renderContext = {
                                        canvasContext: ctx,
                                        viewport: viewport
                                    };
                                    page.render(renderContext);
                                });
                            }

                            document.getElementById('rotate-btn').addEventListener('click', () => {
                                rotation = (rotation + 90) % 360;
                                renderPage(pageNum);
                            });

                            // Initial container adjustment on page load
                            window.addEventListener('load', () => {
                                renderPage(pageNum);
                            });
                        </script>
                    <?php elseif (strpos($fileType, 'image/') === 0): ?>
                        <img id="imgViewer" src="<?= htmlspecialchars($fileUrl) ?>" alt="File Viewer">
                        <script>
                            const imgViewer = document.getElementById('imgViewer');
                            const viewerContainer = document.getElementById('viewerContainer');
                            let rotation = 0;

                            function adjustContainer() {
                                const isLandscape = rotation % 180 !== 0;
                                viewerContainer.style.width = isLandscape ? `${imgViewer.naturalHeight}px` : `${imgViewer.naturalWidth}px`;
                                viewerContainer.style.height = isLandscape ? `${imgViewer.naturalWidth}px` : `${imgViewer.naturalHeight}px`;
                                viewerContainer.style.overflow = 'auto'; // Enable scrolling
                            }

                            // Rotate the image and adjust container size
                            document.getElementById('rotate-btn').addEventListener('click', () => {
                                rotation = (rotation + 90) % 360;
                                imgViewer.style.transform = `rotate(${rotation}deg)`;
                                adjustContainer();
                            });

                            // Initial container adjustment on page load
                            window.addEventListener('load', adjustContainer);
                            imgViewer.addEventListener('load', adjustContainer); // Adjust container when image is loaded
                        </script>
                    <?php else: ?>
                        <p>Unsupported file type: <?= htmlspecialchars($fileType) ?></p>
                    <?php endif; ?>
                </div>
            </body>
            </html>
            <?php
        } else {
            echo "Unsupported file type.";
        }
    } else {
        echo "File does not exist.";
    }
} else {
    echo "No file specified.";
}
include("$root/backend/footer.php");
?>