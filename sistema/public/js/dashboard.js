(() => {
  const palette = ['#176b55', '#3b82a0', '#e7a54a', '#7b6bb3', '#d56b59', '#55a98f', '#758092'];
  const gridColor = '#e8edf0';
  const textColor = '#667085';

  function chartContext(canvas) {
    const bounds = canvas.getBoundingClientRect();
    const ratio = window.devicePixelRatio || 1;
    const width = Math.max(bounds.width, 240);
    const height = Math.max(bounds.height, 180);

    canvas.width = width * ratio;
    canvas.height = height * ratio;

    const context = canvas.getContext('2d');
    context.scale(ratio, ratio);
    context.clearRect(0, 0, width, height);

    return { context, width, height };
  }

  function formatValue(value, currency) {
    const formatted = new Intl.NumberFormat('es-PE', {
      maximumFractionDigits: currency ? 2 : 0,
      minimumFractionDigits: currency ? 2 : 0
    }).format(value);

    return currency ? `S/ ${formatted}` : formatted;
  }

  function drawGrid(context, width, height, padding, maxValue, currency) {
    context.font = '11px system-ui, sans-serif';
    context.textAlign = 'right';
    context.textBaseline = 'middle';

    for (let step = 0; step <= 4; step += 1) {
      const y = padding.top + ((height - padding.top - padding.bottom) * step / 4);
      const value = maxValue * (4 - step) / 4;

      context.strokeStyle = gridColor;
      context.lineWidth = 1;
      context.beginPath();
      context.moveTo(padding.left, y);
      context.lineTo(width - padding.right, y);
      context.stroke();
      context.fillStyle = textColor;
      context.fillText(formatValue(value, currency), padding.left - 8, y);
    }
  }

  function renderLine(canvas, rows) {
    const { context, width, height } = chartContext(canvas);
    const padding = { top: 14, right: 12, bottom: 30, left: 62 };
    const max = Math.max(1, ...rows.map((row) => row.value));
    const plotWidth = width - padding.left - padding.right;
    const plotHeight = height - padding.top - padding.bottom;
    const step = rows.length > 1 ? plotWidth / (rows.length - 1) : 0;

    drawGrid(context, width, height, padding, max, true);
    context.strokeStyle = palette[0];
    context.lineWidth = 2.5;
    context.lineJoin = 'round';
    context.beginPath();

    rows.forEach((row, index) => {
      const x = padding.left + step * index;
      const y = padding.top + plotHeight - (row.value / max) * plotHeight;
      if (index === 0) context.moveTo(x, y);
      else context.lineTo(x, y);
    });

    context.stroke();
    context.fillStyle = palette[0];
    rows.forEach((row, index) => {
      const x = padding.left + step * index;
      const y = padding.top + plotHeight - (row.value / max) * plotHeight;
      context.beginPath();
      context.arc(x, y, 3.5, 0, Math.PI * 2);
      context.fill();
    });

    const labelStep = Math.max(1, Math.ceil(rows.length / 7));
    context.font = '10px system-ui, sans-serif';
    context.fillStyle = textColor;
    context.textAlign = 'center';
    rows.forEach((row, index) => {
      if (index % labelStep !== 0 && index !== rows.length - 1) return;
      const x = padding.left + step * index;
      context.fillText(row.label, x, height - 11);
    });

    canvas.onmousemove = (event) => {
      const rect = canvas.getBoundingClientRect();
      const x = event.clientX - rect.left - padding.left;
      const index = Math.max(0, Math.min(rows.length - 1, Math.round(x / (plotWidth / Math.max(1, rows.length - 1)))));
      canvas.title = `${rows[index].label}: ${formatValue(rows[index].value, true)}`;
    };
  }

  function renderBars(canvas, rows, horizontal, currency) {
    const { context, width, height } = chartContext(canvas);
    const padding = horizontal
      ? { top: 8, right: 16, bottom: 8, left: Math.min(width * 0.48, 170) }
      : { top: 14, right: 12, bottom: 40, left: 62 };
    const max = Math.max(1, ...rows.map((row) => row.value));

    if (horizontal) {
      const rowHeight = (height - padding.top - padding.bottom) / Math.max(rows.length, 1);
      rows.forEach((row, index) => {
        const y = padding.top + index * rowHeight;
        const barWidth = (width - padding.left - padding.right) * row.value / max;
        context.fillStyle = textColor;
        context.font = '11px system-ui, sans-serif';
        context.textAlign = 'right';
        context.textBaseline = 'middle';
        context.fillText(row.label.length > 24 ? `${row.label.slice(0, 22)}…` : row.label, padding.left - 8, y + rowHeight / 2);
        context.fillStyle = palette[index % palette.length];
        context.fillRect(padding.left, y + rowHeight * 0.2, Math.max(barWidth, 1), rowHeight * 0.6);
      });
    } else {
      drawGrid(context, width, height, padding, max, currency);
      const plotWidth = width - padding.left - padding.right;
      const plotHeight = height - padding.top - padding.bottom;
      const slotWidth = plotWidth / Math.max(rows.length, 1);
      rows.forEach((row, index) => {
        const barHeight = (row.value / max) * plotHeight;
        const x = padding.left + index * slotWidth + slotWidth * 0.2;
        const y = padding.top + plotHeight - barHeight;
        context.fillStyle = palette[0];
        context.fillRect(x, y, slotWidth * 0.6, barHeight);
        context.fillStyle = textColor;
        context.font = '10px system-ui, sans-serif';
        context.textAlign = 'center';
        context.textBaseline = 'top';
        context.fillText(row.label, x + slotWidth * 0.3, height - padding.bottom + 8);
      });
    }

    canvas.onmousemove = (event) => {
      const rect = canvas.getBoundingClientRect();
      const relative = horizontal
        ? event.clientY - rect.top - padding.top
        : event.clientX - rect.left - padding.left;
      const size = horizontal
        ? (height - padding.top - padding.bottom) / Math.max(rows.length, 1)
        : (width - padding.left - padding.right) / Math.max(rows.length, 1);
      const index = Math.max(0, Math.min(rows.length - 1, Math.floor(relative / size)));
      if (rows[index]) canvas.title = `${rows[index].label}: ${formatValue(rows[index].value, currency)}`;
    };
  }

  function renderDonut(canvas, rows, currency) {
    const { context, width, height } = chartContext(canvas);
    const total = rows.reduce((sum, row) => sum + row.value, 0);
    const centerX = width / 2;
    const centerY = height / 2;
    const radius = Math.min(width, height) * 0.36;
    canvas.parentElement.querySelector('.dashboard-chart-legend')?.remove();

    if (total <= 0) {
      context.fillStyle = textColor;
      context.font = '13px system-ui, sans-serif';
      context.textAlign = 'center';
      context.fillText('Sin datos para mostrar', centerX, centerY);
      return;
    }

    let start = -Math.PI / 2;
    rows.forEach((row, index) => {
      const end = start + (row.value / total) * Math.PI * 2;
      context.beginPath();
      context.arc(centerX, centerY, radius, start, end);
      context.arc(centerX, centerY, radius * 0.58, end, start, true);
      context.closePath();
      context.fillStyle = palette[index % palette.length];
      context.fill();
      start = end;
    });

    context.fillStyle = textColor;
    context.font = '11px system-ui, sans-serif';
    context.textAlign = 'center';
    context.textBaseline = 'middle';
    context.fillText(formatValue(total, currency), centerX, centerY);

    const legend = document.createElement('ul');
    legend.className = 'dashboard-chart-legend';
    rows.forEach((row, index) => {
      const item = document.createElement('li');
      const dot = document.createElement('span');
      dot.className = 'dashboard-chart-legend-dot';
      dot.style.backgroundColor = palette[index % palette.length];
      const label = document.createElement('span');
      label.textContent = `${row.label}: ${formatValue(row.value, currency)}`;
      item.append(dot, label);
      legend.append(item);
    });
    canvas.parentElement.append(legend);

    canvas.onmousemove = (event) => {
      const rect = canvas.getBoundingClientRect();
      const dx = event.clientX - rect.left - centerX;
      const dy = event.clientY - rect.top - centerY;
      const angle = (Math.atan2(dy, dx) + Math.PI / 2 + Math.PI * 2) % (Math.PI * 2);
      let totalAngle = 0;
      const selected = rows.find((row) => {
        totalAngle += (row.value / total) * Math.PI * 2;
        return angle <= totalAngle;
      });
      if (selected) canvas.title = `${selected.label}: ${formatValue(selected.value, currency)}`;
    };
  }

  function initialize() {
    const root = document.getElementById('dashboard-charts');
    if (!root) return;

    let charts;
    try {
      charts = JSON.parse(root.dataset.chartData);
    } catch (error) {
      console.error('No se pudieron leer los datos de los gráficos.', error);
      return;
    }

    const draw = () => {
      const daily = document.getElementById('daily-sales-chart');
      const monthly = document.getElementById('monthly-sales-chart');
      const topMedicines = document.getElementById('top-medicines-chart');
      const categories = document.getElementById('sales-category-chart');
      const inventory = document.getElementById('inventory-category-chart');

      if (daily) renderLine(daily, charts.daily);
      if (monthly) renderBars(monthly, charts.monthly, false, true);
      if (topMedicines) renderBars(topMedicines, charts.topMedicines, true, false);
      if (categories) renderDonut(categories, charts.categories, true);
      if (inventory) renderDonut(inventory, charts.inventoryCategories, false);
    };

    draw();
    let resizeTimer;
    window.addEventListener('resize', () => {
      window.clearTimeout(resizeTimer);
      resizeTimer = window.setTimeout(draw, 120);
    });
  }

  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', initialize, { once: true });
  } else {
    initialize();
  }
})();
