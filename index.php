<?php
session_start();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>IEP Form Analyzer</title>
    <link rel="stylesheet" href="assets/style.css">
    <!-- Chart.js for bar graph visualization -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <!-- Favicon -->
    <link rel="icon" href="data:image/svg+xml,<svg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 100 100'><text y='.9em' font-size='90'>📋</text></svg>">
    <style>
        .nav-link {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, 'Helvetica Neue', Arial, sans-serif;
        }
    </style>
</head>
<body>
    <!-- Navigation Bar -->
    <nav>
        <div class="nav-container">
            <a href="../../" class="nav-brand">
                <span class="nav-brand-icon">📋</span>
                <span>GuideAI</span>
            </a>
            <button class="nav-toggle" id="navToggle">
                <span>☰</span>
            </button>
            <ul class="nav-links" id="navLinks">
                <li><a href="../../" class="nav-link" data-page="home">🏠 Tools</a></li>
                <li class="nav-divider"></li>
                <li><a href="./" class="nav-link" data-page="analyzer">📋 IEP Analyzer</a></li>
                <li class="nav-divider"></li>
                <li><a href="diagnose" class="nav-link" data-page="diagnose">🔍 Diagnose PDF</a></li>
                <li class="nav-divider"></li>
                <li><a href="../al/" class="nav-link" data-page="accommodations">🏠 Accommodations</a></li>
                <li class="nav-divider"></li>
                <li><a href="#" class="nav-link" id="quickGuideLink">📖 Quick Guide</a></li>
            </ul>
        </div>
    </nav>

    <div class="container">
        <header class="header">
            <h1>IEP Form Analyzer</h1>
            <p class="subtitle">Extract and display Individual Educational Program data</p>
        </header>

        <main class="main-content">
            <!-- Upload Section -->
            <section class="upload-section">
                <div class="upload-area" id="uploadArea">
                    <svg class="upload-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"></path>
                        <polyline points="17 8 12 3 7 8"></polyline>
                        <line x1="12" y1="3" x2="12" y2="15"></line>
                    </svg>
                    <h2>Upload IEP PDF</h2>
                    <p>Drag and drop your PDF form here or click to browse</p>
                    <input type="file" id="fileInput" accept=".pdf" hidden>
                </div>
                <div class="upload-status" id="uploadStatus" style="display: none;">
                    <div class="spinner"></div>
                    <p id="statusText">Processing PDF...</p>
                </div>
            </section>

            <!-- Results Section -->
            <section class="results-section" id="resultsSection" style="display: none;">
                <div class="results-header">
                    <h2>Extracted IEP Data</h2>
                    <button class="btn-secondary" id="uploadNewBtn">Upload Another</button>
                </div>

                <!-- Student Summary Card -->
                <div class="student-card" id="studentCard">
                    <div class="card-row">
                        <div class="card-item">
                            <label>Student Name</label>
                            <p id="studentName">-</p>
                        </div>
                        <div class="card-item">
                            <label>Date of Birth</label>
                            <p id="studentDOB">-</p>
                        </div>
                        <div class="card-item">
                            <label>Grade</label>
                            <p id="studentGrade">-</p>
                        </div>
                    </div>
                    <div class="card-row">
                        <div class="card-item">
                            <label>Disability Category</label>
                            <p id="disabilityCategory">-</p>
                        </div>
                        <div class="card-item">
                            <label>IEP Meeting Date</label>
                            <p id="meetingDate">-</p>
                        </div>
                        <div class="card-item">
                            <label>Effective Date</label>
                            <p id="effectiveDate">-</p>
                        </div>
                    </div>
                </div>

                <!-- Collapsible Sections -->
                <div class="sections-container">
                    <!-- Current Performance Levels -->
                    <section class="collapsible-section">
                        <button class="section-header" data-section="performance">
                            <span class="toggle-icon">▶</span>
                            <h3>Current Performance Levels</h3>
                        </button>
                        <div class="section-content" id="performance" style="display: none;">
                            <div id="performanceContent" class="content-empty">No data extracted</div>
                        </div>
                    </section>

                    <!-- Goals Visualization -->
                    <section class="collapsible-section">
                        <button class="section-header" data-section="goalsViz">
                            <span class="toggle-icon">▶</span>
                            <h3>📊 Goals Progress Visualization</h3>
                        </button>
                        <div class="section-content" id="goalsViz" style="display: none;">
                            <div id="goalsVisualization" class="content-empty">
                                <canvas id="goalsChart" style="max-height: 400px; margin: 20px 0;"></canvas>
                                <p id="vizMessage" style="text-align: center; color: #666;">Processing goal data...</p>
                            </div>
                        </div>
                    </section>

                    <!-- Annual Goals -->
                    <section class="collapsible-section">
                        <button class="section-header" data-section="goals">
                            <span class="toggle-icon">▶</span>
                            <h3>Annual IEP Goals</h3>
                        </button>
                        <div class="section-content" id="goals" style="display: none;">
                            <div id="goalsContent" class="content-empty">No goals extracted</div>
                        </div>
                    </section>

                    <!-- Accommodations & Modifications -->
                    <section class="collapsible-section">
                        <button class="section-header" data-section="accommodations">
                            <span class="toggle-icon">▶</span>
                            <h3>Accommodations & Modifications</h3>
                        </button>
                        <div class="section-content" id="accommodations" style="display: none;">
                            <div id="accommodationsContent" class="content-empty">No data extracted</div>
                        </div>
                    </section>

                    <!-- Related Services -->
                    <section class="collapsible-section">
                        <button class="section-header" data-section="services">
                            <span class="toggle-icon">▶</span>
                            <h3>Related Services</h3>
                        </button>
                        <div class="section-content" id="services" style="display: none;">
                            <div id="servicesContent" class="content-empty">No services extracted</div>
                        </div>
                    </section>

                    <!-- Transition Planning -->
                    <section class="collapsible-section">
                        <button class="section-header" data-section="transition">
                            <span class="toggle-icon">▶</span>
                            <h3>Transition Planning</h3>
                        </button>
                        <div class="section-content" id="transition" style="display: none;">
                            <div id="transitionContent" class="content-empty">No data extracted</div>
                        </div>
                    </section>

                    <!-- Pain Points -->
                    <section class="collapsible-section">
                        <button class="section-header" data-section="painPoints">
                            <span class="toggle-icon">▶</span>
                            <h3>⚠️ Pain Points & Recommendations</h3>
                        </button>
                        <div class="section-content" id="painPoints" style="display: none;">
                            <div id="painPointsContent" class="content-empty">No issues identified</div>
                        </div>
                    </section>

                </div>

                <!-- Export Options -->
                <div class="export-options">
                    <button class="btn-export" id="exportJson">Export as JSON</button>
                    <button class="btn-export" id="exportPrint">Print Friendly</button>
                </div>
            </section>

            <!-- Error Section -->
            <section class="error-section" id="errorSection" style="display: none;">
                <div class="error-box">
                    <h3>Error Processing PDF</h3>
                    <p id="errorMessage"></p>
                    <button class="btn-secondary" id="tryAgainBtn">Try Again</button>
                </div>
            </section>
        </main>
    </div>


    <script>
        // Navigation functionality
        document.addEventListener('DOMContentLoaded', () => {
            const navToggle = document.getElementById('navToggle');
            const navLinks = document.getElementById('navLinks');
            const navLinkItems = document.querySelectorAll('.nav-link');

            // Toggle mobile menu
            navToggle.addEventListener('click', () => {
                navLinks.classList.toggle('active');
            });

            // Close menu when a link is clicked
            navLinkItems.forEach(link => {
                link.addEventListener('click', (e) => {
                    // Only close for internal navigation
                    if (link.getAttribute('href') !== '#') {
                        navLinks.classList.remove('active');
                    }
                });
            });

            // Set active nav link based on current page
            const currentPage = window.location.pathname.split('/').pop() || 'index';
            const pageNameOnly = currentPage.replace('.php', '');
            navLinkItems.forEach(link => {
                const href = link.getAttribute('href');
                if (href && (href === pageNameOnly || href === currentPage || (currentPage === '' && href === 'index'))) {
                    link.classList.add('active');
                }
            });

            // Quick guide functionality
            const quickGuideLink = document.getElementById('quickGuideLink');
            if (quickGuideLink) {
                quickGuideLink.addEventListener('click', (e) => {
                    e.preventDefault();
                    alert('📖 Quick Guide:\n\n1. Upload an IEP PDF\n2. The analyzer extracts key data\n3. Review goals, services, and accommodations\n4. Check pain points for potential issues\n\nFor detailed help, visit the Diagnose PDF page!');
                });
            }
        });

    </script>

    <script src="assets/app.js"></script>
</body>
</html>
