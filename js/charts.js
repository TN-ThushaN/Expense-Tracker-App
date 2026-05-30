const COLORS = {
  income:  '#1D9E75',
  expense: '#E05252',
  grid:    'rgba(0,0,0,0.06)',
  label:   '#6b7280',
  slices: [
    '#1D9E75','#378ADD','#E05252',
    '#BA7517','#7F77DD','#D4537E',
    '#0F6E56','#185FA5'
  ]
};

Chart.defaults.font.family = "'Segoe UI', system-ui, sans-serif";
Chart.defaults.font.size   = 12;

// ── Pie / doughnut chart ──────────────────────────────────
if (pieData.values.length > 0) {
  const pieCtx = document.getElementById('pieChart');
  if (pieCtx) {
    new Chart(pieCtx, {
      type: 'doughnut',
      data: {
        labels:   pieData.labels,
        datasets: [{
          data:            pieData.values,
          backgroundColor: COLORS.slices,
          borderWidth:     2,
          borderColor:     '#fff',
          hoverOffset:     6
        }]
      },
      options: {
        responsive:  true,
        cutout:      '62%',
        plugins: {
          legend: {
            position: 'bottom',
            labels: {
              padding:   16,
              boxWidth:  12,
              boxHeight: 12,
              color: COLORS.label
            }
          },
          tooltip: {
            callbacks: {
              label: ctx => {
                const val   = ctx.parsed;
                const total = ctx.dataset.data.reduce((a, b) => a + b, 0);
                const pct   = ((val / total) * 100).toFixed(1);
                return ` LKR ${val.toLocaleString()}  (${pct}%)`;
              }
            }
          }
        }
      }
    });
  }
}

// ── Bar chart ─────────────────────────────────────────────
const barCtx = document.getElementById('barChart');
if (barCtx) {
  new Chart(barCtx, {
    type: 'bar',
    data: {
      labels: barData.labels,
      datasets: [
        {
          label:           'Income',
          data:            barData.income,
          backgroundColor: COLORS.income,
          borderRadius:    5,
          borderSkipped:   false
        },
        {
          label:           'Expenses',
          data:            barData.expense,
          backgroundColor: COLORS.expense,
          borderRadius:    5,
          borderSkipped:   false
        }
      ]
    },
    options: {
      responsive: true,
      plugins: {
        legend: {
          labels: { color: COLORS.label, boxWidth: 12, boxHeight: 12 }
        },
        tooltip: {
          callbacks: {
            label: ctx => ` LKR ${ctx.parsed.y.toLocaleString()}`
          }
        }
      },
      scales: {
        x: {
          grid:  { display: false },
          ticks: { color: COLORS.label }
        },
        y: {
          grid:  { color: COLORS.grid },
          ticks: {
            color: COLORS.label,
            callback: v => 'LKR ' + (v >= 1000 ? (v/1000).toFixed(0)+'k' : v)
          },
          beginAtZero: true
        }
      }
    }
  });
}