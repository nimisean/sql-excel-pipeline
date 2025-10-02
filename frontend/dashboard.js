class Dashboard {
    constructor() {
        this.init();
        this.setDefaultDates();
    }
    
    init() {
        document.getElementById('reportForm').addEventListener('submit', (e) => {
            e.preventDefault();
            this.generateReport();
        });
    }
    
    setDefaultDates() {
        const today = new Date().toISOString().split('T')[0];
        const firstDay = new Date(new Date().getFullYear(), new Date().getMonth(), 1)
                        .toISOString().split('T')[0];
        
        document.getElementById('startDate').value = firstDay;
        document.getElementById('endDate').value = today;
    }
    
    async generateReport() {
        const generateBtn = document.getElementById('generateBtn');
        const formData = new FormData(document.getElementById('reportForm'));
        
        generateBtn.textContent = 'Generating...';
        generateBtn.disabled = true;
        
        try {
            const response = await fetch('../backend/export.php', {
                method: 'POST',
                body: formData
            });
            
            const result = await response.json();
            
            if (result.status === 'success') {
                this.showDownloadSection(result);
                this.showDataPreview(formData);
            } else {
                throw new Error('Report generation failed');
            }
            
        } catch (error) {
            console.error('Error:', error);
            alert('Error generating report: ' + error.message);
        } finally {
            generateBtn.textContent = 'Generate Excel Report';
            generateBtn.disabled = false;
        }
    }
    
    showDownloadSection(result) {
        const downloadSection = document.getElementById('downloadSection');
        const downloadLink = document.getElementById('downloadLink');
        
        downloadLink.href = result.download_url;
        downloadLink.download = result.filename;
        downloadSection.style.display = 'block';
        
        // Scroll to download section
        downloadSection.scrollIntoView({ behavior: 'smooth' });
    }
    
    async showDataPreview(formData) {
        // Simulate data preview - in real app, you'd fetch actual data
        const previewData = [
            ['Sale ID', 'Date', 'Product', 'Amount', 'Region'],
            ['1001', '2024-01-15', 'Product A', '$150.00', 'North'],
            ['1002', '2024-01-15', 'Product B', '$200.00', 'South'],
            ['1003', '2024-01-16', 'Product C', '$175.50', 'East'],
            ['1004', '2024-01-16', 'Product A', '$300.00', 'West']
        ];
        
        const previewElement = document.getElementById('dataPreview');
        previewElement.innerHTML = this.createPreviewTable(previewData);
        
        this.renderChart(previewData);
    }
    
    createPreviewTable(data) {
        return `
            <table style="width: 100%; border-collapse: collapse;">
                <thead>
                    <tr style="background: #f8f9fa;">
                        ${data[0].map(header => `<th style="padding: 8px; border: 1px solid #dee2e6; text-align: left;">${header}</th>`).join('')}
                    </tr>
                </thead>
                <tbody>
                    ${data.slice(1).map(row => `
                        <tr>
                            ${row.map(cell => `<td style="padding: 8px; border: 1px solid #dee2e6;">${cell}</td>`).join('')}
                        </tr>
                    `).join('')}
                </tbody>
            </table>
        `;
    }
    
    renderChart(data) {
        const ctx = document.getElementById('salesChart').getContext('2d');
        
        // Extract chart data from preview
        const products = {};
        data.slice(1).forEach(row => {
            const product = row[2];
            const amount = parseFloat(row[3].replace('$', ''));
            products[product] = (products[product] || 0) + amount;
        });
        
        new Chart(ctx, {
            type: 'bar',
            data: {
                labels: Object.keys(products),
                datasets: [{
                    label: 'Sales by Product',
                    data: Object.values(products),
                    backgroundColor: [
                        '#3498db', '#2ecc71', '#e74c3c', '#f39c12'
                    ],
                    borderWidth: 1
                }]
            },
            options: {
                responsive: true,
                plugins: {
                    title: {
                        display: true,
                        text: 'Sales Distribution'
                    }
                }
            }
        });
    }
}

// Initialize dashboard when page loads
document.addEventListener('DOMContentLoaded', () => {
    new Dashboard();
});
