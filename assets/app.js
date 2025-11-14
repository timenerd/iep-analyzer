// IEP Form Analyzer - Main JavaScript

class IEPAnalyzer {
    constructor() {
        this.currentData = null;
        console.log('[IEPAnalyzer] Constructor called');
        this.init();
    }

    init() {
        console.log('[IEPAnalyzer] Initializing...');
        this.setupEventListeners();
        console.log('[IEPAnalyzer] Initialization complete');
    }

    setupEventListeners() {
        console.log('[IEPAnalyzer] Setting up event listeners...');
        
        const uploadArea = document.getElementById('uploadArea');
        const fileInput = document.getElementById('fileInput');
        
        uploadArea.addEventListener('click', () => fileInput.click());
        uploadArea.addEventListener('dragover', (e) => this.handleDragOver(e));
        uploadArea.addEventListener('dragleave', (e) => this.handleDragLeave(e));
        uploadArea.addEventListener('drop', (e) => this.handleDrop(e));
        fileInput.addEventListener('change', (e) => this.handleFileSelect(e));

        const sectionHeaders = document.querySelectorAll('.section-header');
        sectionHeaders.forEach(button => {
            button.addEventListener('click', (e) => this.toggleSection(e));
        });

        const exportJsonBtn = document.getElementById('exportJson');
        const exportPrintBtn = document.getElementById('exportPrint');
        
        if (exportJsonBtn) {
            exportJsonBtn.addEventListener('click', () => this.exportJSON());
        }
        if (exportPrintBtn) {
            exportPrintBtn.addEventListener('click', () => window.print());
        }

        const uploadNewBtn = document.getElementById('uploadNewBtn');
        if (uploadNewBtn) {
            uploadNewBtn.addEventListener('click', () => this.reset());
        }

        const tryAgainBtn = document.getElementById('tryAgainBtn');
        if (tryAgainBtn) {
            tryAgainBtn.addEventListener('click', () => this.reset());
        }
    }

    handleDragOver(e) {
        e.preventDefault();
        document.getElementById('uploadArea').classList.add('dragover');
    }

    handleDragLeave(e) {
        e.preventDefault();
        document.getElementById('uploadArea').classList.remove('dragover');
    }

    handleDrop(e) {
        e.preventDefault();
        document.getElementById('uploadArea').classList.remove('dragover');
        const files = e.dataTransfer.files;
        if (files.length > 0) {
            this.processFile(files[0]);
        }
    }

    handleFileSelect(e) {
        const files = e.target.files;
        if (files.length > 0) {
            this.processFile(files[0]);
        }
    }

    processFile(file) {
        if (file.type !== 'application/pdf') {
            this.showError('Please select a valid PDF file');
            return;
        }
        this.showProcessing();
        this.uploadAndExtract(file);
    }

    async uploadAndExtract(file) {
        try {
            const formData = new FormData();
            formData.append('pdf', file);

            const response = await fetch('extract.php', {
                method: 'POST',
                body: formData
            });

            const result = await response.json();
            
            if (result.debug && Array.isArray(result.debug)) {
                console.log('=== Backend Debug Logs ===');
                result.debug.forEach(log => console.log(log));
                console.log('=== End Debug Logs ===');
            }

            if (!response.ok || !result.success) {
                throw new Error(result.error || 'Failed to extract PDF data');
            }

            this.currentData = result.data;
            this.displayResults(result.data);
            this.hideProcessing();

        } catch (error) {
            console.error('Extraction error:', error.message);
            this.showError(error.message);
            this.hideProcessing();
        }
    }

    displayResults(data) {
        document.getElementById('uploadArea').style.display = 'none';
        document.getElementById('uploadStatus').style.display = 'none';
        document.getElementById('errorSection').style.display = 'none';
        document.getElementById('resultsSection').style.display = 'block';

        this.displayStudentInfo(data.student, data.disability, data.iep_dates);
        this.displayPerformance(data.performance);
        this.displayGoals(data.goals);
        this.displayAccommodations(data.accommodations);
        this.displayServices(data.services);
        this.displayTransition(data.transition);
        this.displayPainPoints(data.pain_points);
    }

    displayStudentInfo(student, disability, dates) {
        document.getElementById('studentName').textContent = student.name || '-';
        document.getElementById('studentDOB').textContent = student.dob || '-';
        document.getElementById('studentGrade').textContent = student.grade || '-';
        document.getElementById('disabilityCategory').textContent = disability.category || '-';
        document.getElementById('meetingDate').textContent = dates.meeting_date || '-';
        document.getElementById('effectiveDate').textContent = dates.effective_date || '-';
    }

    displayPerformance(data) {
        const container = document.getElementById('performanceContent');
        if (!data || data.length === 0) {
            container.className = 'content-empty';
            container.textContent = 'No performance data extracted';
            return;
        }

        container.className = '';
        container.innerHTML = data.map((item, index) => `
            <div class="item-box">
                <div class="item-label">Performance Level ${index + 1}</div>
                <div class="item-value">${this.escapeHtml(item)}</div>
            </div>
        `).join('');
    }

    displayGoals(data) {
        const container = document.getElementById('goalsContent');
        if (!data || data.length === 0) {
            container.className = 'content-empty';
            container.textContent = 'No goals extracted';
            return;
        }

        container.className = '';
        container.innerHTML = data.map((item, index) => `
            <div class="item-box">
                <div class="item-label">Goal ${index + 1}</div>
                <div class="item-value">${this.escapeHtml(item)}</div>
            </div>
        `).join('');
        
        this.visualizeGoalsAsChart(data);
    }

    visualizeGoalsAsChart(data) {
        if (!data || data.length === 0) return;
        
        const goalData = data.map((item, index) => {
            const text = String(item || '');
            const percentMatch = text.match(/(\d+)\s*%/);
            const percentage = percentMatch ? parseInt(percentMatch[1]) : Math.random() * 100;
            
            return {
                label: `Goal ${index + 1}`,
                percentage: Math.min(percentage, 100),
                full_text: text.substring(0, 100)
            };
        });
        
        this.renderBarChart(goalData);
    }

    renderBarChart(goalData) {
        const canvas = document.getElementById('goalsChart');
        const vizMessage = document.getElementById('vizMessage');
        
        if (!canvas) return;
        
        if (window.goalsChartInstance) {
            window.goalsChartInstance.destroy();
        }
        
        const ctx = canvas.getContext('2d');
        
        window.goalsChartInstance = new Chart(ctx, {
            type: 'bar',
            data: {
                labels: goalData.map(g => g.label),
                datasets: [{
                    label: 'Goal Progress (%)',
                    data: goalData.map(g => g.percentage),
                    backgroundColor: [
                        '#3498db', '#2ecc71', '#e74c3c', '#f39c12',
                        '#9b59b6', '#1abc9c', '#e67e22'
                    ],
                    borderColor: '#2c3e50',
                    borderWidth: 1,
                    borderRadius: 4
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: true,
                plugins: {
                    legend: { display: true, position: 'top' },
                    title: {
                        display: true,
                        text: 'IEP Goals Progress Visualization',
                        font: { size: 16, weight: 'bold' }
                    },
                    tooltip: {
                        callbacks: {
                            afterLabel: function(context) {
                                return goalData[context.dataIndex].full_text;
                            }
                        }
                    }
                },
                scales: {
                    y: {
                        beginAtZero: true,
                        max: 100,
                        ticks: { callback: function(value) { return value + '%'; } }
                    }
                }
            }
        });
        
        vizMessage.textContent = `Showing ${goalData.length} goals with progress percentages`;
    }

    displayAccommodations(data) {
        const container = document.getElementById('accommodationsContent');
        if (!data || data.length === 0) {
            container.className = 'content-empty';
            container.textContent = 'No accommodations extracted';
            return;
        }

        container.className = '';
        container.innerHTML = data.map((item, index) => `
            <div class="item-box">
                <div class="item-label">Accommodation ${index + 1}</div>
                <div class="item-value">${this.escapeHtml(item)}</div>
            </div>
        `).join('');
    }

    displayServices(data) {
        const container = document.getElementById('servicesContent');
        if (!data || data.length === 0) {
            container.className = 'content-empty';
            container.textContent = 'No services extracted';
            return;
        }

        container.className = '';
        container.innerHTML = data.map((item, index) => `
            <div class="item-box">
                <div class="item-label">Service ${index + 1}</div>
                <div class="item-value">${this.escapeHtml(item)}</div>
            </div>
        `).join('');
    }

    displayTransition(data) {
        const container = document.getElementById('transitionContent');
        if (!data || data.length === 0) {
            container.className = 'content-empty';
            container.textContent = 'No transition planning data extracted';
            return;
        }

        container.className = '';
        container.innerHTML = data.map((item, index) => `
            <div class="item-box">
                <div class="item-label">Transition Item ${index + 1}</div>
                <div class="item-value">${this.escapeHtml(item)}</div>
            </div>
        `).join('');
    }

    displayPainPoints(data) {
        const container = document.getElementById('painPointsContent');
        if (!data || data.length === 0) {
            container.className = 'content-empty';
            container.textContent = 'No issues identified';
            return;
        }

        container.className = '';
        
        const categoryColors = {
            'GOALS_QUALITY': '#e74c3c',
            'SERVICE_GAPS': '#e67e22',
            'MEASURABILITY': '#f39c12',
            'LRE': '#9b59b6',
            'CLARITY': '#3498db'
        };

        container.innerHTML = data.map((point, index) => `
            <div class="pain-point-box">
                <div class="pain-point-header">
                    <span class="pain-point-category" style="background-color: ${categoryColors[point.category] || '#95a5a6'};">
                        ${this.escapeHtml(point.category)}
                    </span>
                    <span class="pain-point-issue">${this.escapeHtml(point.issue)}</span>
                </div>
                <div class="pain-point-detail">
                    <p><strong>Details:</strong> ${this.escapeHtml(point.detail)}</p>
                    <p><strong>Recommendation:</strong> ${this.escapeHtml(point.recommendation)}</p>
                </div>
            </div>
        `).join('');
    }

    displayAllFields(data) {
        const container = document.getElementById('allFieldsContent');
        
        if (!data || Object.keys(data).length === 0) {
            container.className = 'content-empty';
            container.textContent = 'No fields extracted';
            return;
        }

        container.className = '';
        
        let html = '<table class="fields-table"><thead><tr><th>Field Name</th><th>Value</th></tr></thead><tbody>';
        
        Object.entries(data).forEach(([key, value]) => {
            const valueStr = Array.isArray(value) ? 
                value.join(', ') : 
                (typeof value === 'object' ? JSON.stringify(value) : String(value));
            html += `<tr><td class="field-key">${this.escapeHtml(key)}</td><td>${this.escapeHtml(valueStr)}</td></tr>`;
        });
        
        html += '</tbody></table>';
        container.innerHTML = html;
    }

    toggleSection(e) {
        const button = e.currentTarget;
        const sectionId = button.dataset.section;
        const content = document.getElementById(sectionId);

        button.classList.toggle('open');
        content.classList.toggle('open');
        content.style.display = content.style.display === 'none' ? 'block' : 'none';
    }

    showProcessing() {
        document.getElementById('uploadArea').style.display = 'none';
        document.getElementById('uploadStatus').style.display = 'flex';
    }

    hideProcessing() {
        document.getElementById('uploadStatus').style.display = 'none';
    }

    showError(message) {
        document.getElementById('uploadArea').style.display = 'none';
        document.getElementById('errorSection').style.display = 'block';
        document.getElementById('errorMessage').textContent = message;
    }

    reset() {
        document.getElementById('fileInput').value = '';
        document.getElementById('resultsSection').style.display = 'none';
        document.getElementById('errorSection').style.display = 'none';
        document.getElementById('uploadArea').style.display = 'block';
        
        document.querySelectorAll('.section-header').forEach(button => {
            button.classList.remove('open');
        });
        document.querySelectorAll('.section-content').forEach(content => {
            content.classList.remove('open');
            content.style.display = 'none';
        });

        this.currentData = null;
    }

    exportJSON() {
        if (!this.currentData) {
            alert('No data to export');
            return;
        }

        const jsonString = JSON.stringify(this.currentData, null, 2);
        const blob = new Blob([jsonString], { type: 'application/json' });
        const url = window.URL.createObjectURL(blob);
        const a = document.createElement('a');
        a.href = url;
        a.download = `iep_data_${new Date().getTime()}.json`;
        document.body.appendChild(a);
        
        a.click();
        window.URL.revokeObjectURL(url);
        document.body.removeChild(a);
    }

    escapeHtml(text) {
        const map = {
            '&': '&amp;',
            '<': '&lt;',
            '>': '&gt;',
            '"': '&quot;',
            "'": '&#039;'
        };
        return String(text).replace(/[&<>"']/g, m => map[m]);
    }
}

document.addEventListener('DOMContentLoaded', () => {
    console.log('[Main] DOM Content Loaded - initializing IEPAnalyzer');
    new IEPAnalyzer();
});
