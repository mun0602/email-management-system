// Chart creation helpers for Mail Management System
function createPieChart(canvasId, data, labels) {
    const canvas = document.getElementById(canvasId);
    if (!canvas) return null;
    
    // Simple fallback if Chart.js is not available
    if (typeof Chart === 'undefined') {
        canvas.parentElement.innerHTML = `
            <div class="text-center py-4">
                <h6>Phân bổ Mail theo ứng dụng</h6>
                <div class="row">
                    ${labels.map((label, index) => `
                        <div class="col-6 mb-2">
                            <div class="d-flex justify-content-between">
                                <span>${label}</span>
                                <span class="badge bg-primary">${data[index]}</span>
                            </div>
                        </div>
                    `).join('')}
                </div>
            </div>
        `;
        return null;
    }
    
    const ctx = canvas.getContext('2d');
    return new Chart(ctx, {
        type: 'pie',
        data: {
            labels: labels,
            datasets: [{
                data: data,
                backgroundColor: [
                    '#007bff', '#28a745', '#ffc107', '#dc3545', 
                    '#6f42c1', '#fd7e14', '#20c997', '#6c757d'
                ]
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: {
                    position: 'bottom'
                }
            }
        }
    });
}

function createBarChart(canvasId, data, labels) {
    const canvas = document.getElementById(canvasId);
    if (!canvas) return null;
    
    // Simple fallback if Chart.js is not available
    if (typeof Chart === 'undefined') {
        canvas.parentElement.innerHTML = `
            <div class="text-center py-4">
                <h6>Biểu đồ thống kê</h6>
                <div class="row">
                    ${labels.map((label, index) => `
                        <div class="col-12 mb-2">
                            <div class="d-flex justify-content-between align-items-center">
                                <span>${label}</span>
                                <div class="progress flex-grow-1 mx-2" style="height: 20px;">
                                    <div class="progress-bar bg-primary" role="progressbar" 
                                         style="width: ${Math.max(data[index] / Math.max(...data) * 100, 5)}%">
                                        ${data[index]}
                                    </div>
                                </div>
                            </div>
                        </div>
                    `).join('')}
                </div>
            </div>
        `;
        return null;
    }
    
    const ctx = canvas.getContext('2d');
    return new Chart(ctx, {
        type: 'bar',
        data: {
            labels: labels,
            datasets: [{
                label: 'Số lượng mail',
                data: data,
                backgroundColor: '#007bff',
                borderColor: '#0056b3',
                borderWidth: 1
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            scales: {
                y: {
                    beginAtZero: true
                }
            }
        }
    });
}

function createLineChart(canvasId, data, labels) {
    const canvas = document.getElementById(canvasId);
    if (!canvas) return null;
    
    // Simple fallback if Chart.js is not available
    if (typeof Chart === 'undefined') {
        canvas.parentElement.innerHTML = `
            <div class="text-center py-4">
                <h6>Xu hướng thời gian</h6>
                <div class="row">
                    ${labels.map((label, index) => `
                        <div class="col-6 col-md-4 mb-2">
                            <div class="text-center">
                                <div class="fs-4 text-primary">${data[index]}</div>
                                <small class="text-muted">${label}</small>
                            </div>
                        </div>
                    `).join('')}
                </div>
            </div>
        `;
        return null;
    }
    
    const ctx = canvas.getContext('2d');
    return new Chart(ctx, {
        type: 'line',
        data: {
            labels: labels,
            datasets: [{
                label: 'Số lượng mail',
                data: data,
                borderColor: '#007bff',
                backgroundColor: 'rgba(0, 123, 255, 0.1)',
                tension: 0.4
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            scales: {
                y: {
                    beginAtZero: true
                }
            }
        }
    });
}