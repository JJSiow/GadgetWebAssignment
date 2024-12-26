$(() => {
    document.querySelectorAll('.bar').forEach(bar => {
        bar.addEventListener('mouseenter', function () {
            const info = this.getAttribute('data-info');
            const tooltip = document.createElement('div');
            tooltip.className = 'tooltip';
            tooltip.textContent = info;
            tooltip.style.position = 'absolute';
            tooltip.style.background = '#333';
            tooltip.style.color = '#fff';
            tooltip.style.padding = '5px';
            tooltip.style.borderRadius = '5px';
            tooltip.style.top = `${this.offsetTop + 310}px`;
            tooltip.style.left = `${this.offsetLeft + 180}px`;
            document.body.appendChild(tooltip);

            bar.addEventListener('mouseleave', () => {
                document.body.removeChild(tooltip);
            });
        });
    });

    // Pie Chart Labels
    const pieChart = document.getElementById('pie-chart');
    const labels = [
        { percentage: '20%', label: 'Electronics', color: '#4CAF50', x: '35%', y: '10%' },
        { percentage: '30%', label: 'Clothing', color: '#FF9800', x: '80%', y: '50%' },
        { percentage: '20%', label: 'Groceries', color: '#2196F3', x: '35%', y: '85%' },
        { percentage: '30%', label: 'Tv', color: '#2196F3', x: '35%', y: '85%' },
    ];
    labels.forEach(label => {
        const span = document.createElement('span');
        span.textContent = `${label.label} (${label.percentage})`;
        span.style.position = 'absolute';
        span.style.color = label.color;
        span.style.left = label.x;
        span.style.top = label.y;
        pieChart.appendChild(span);
    });
});