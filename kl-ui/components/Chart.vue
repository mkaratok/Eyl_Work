<template>
  <div class="chart-container w-full h-full">
    <canvas ref="chartCanvas" class="w-full h-full"></canvas>
  </div>
</template>

<script setup>
import { ref, onMounted, watch, nextTick } from 'vue'

const props = defineProps({
  type: {
    type: String,
    default: 'line',
    validator: (value) => ['line', 'bar', 'pie', 'doughnut'].includes(value)
  },
  data: {
    type: Array,
    required: true
  },
  labels: {
    type: Array,
    required: true
  },
  options: {
    type: Object,
    default: () => ({})
  }
})

const chartCanvas = ref(null)
let chartInstance = null

const defaultOptions = {
  responsive: true,
  maintainAspectRatio: false,
  plugins: {
    legend: {
      position: 'top',
    },
    title: {
      display: false,
    },
  },
  scales: {
    y: {
      beginAtZero: true,
      grid: {
        color: 'rgba(0, 0, 0, 0.1)',
      },
      ticks: {
        color: 'rgba(0, 0, 0, 0.6)',
      }
    },
    x: {
      grid: {
        color: 'rgba(0, 0, 0, 0.1)',
      },
      ticks: {
        color: 'rgba(0, 0, 0, 0.6)',
      }
    }
  }
}

const getChartData = () => {
  const colors = {
    line: 'rgba(59, 130, 246, 1)',
    bar: 'rgba(34, 197, 94, 1)',
    pie: [
      'rgba(59, 130, 246, 1)',
      'rgba(34, 197, 94, 1)', 
      'rgba(251, 146, 60, 1)',
      'rgba(239, 68, 68, 1)',
      'rgba(168, 85, 247, 1)',
      'rgba(236, 72, 153, 1)',
      'rgba(14, 165, 233, 1)'
    ]
  }

  const backgroundColor = props.type === 'pie' || props.type === 'doughnut' 
    ? colors.pie.slice(0, props.data.length)
    : props.type === 'bar' 
      ? colors.bar 
      : 'rgba(59, 130, 246, 0.1)'

  const borderColor = props.type === 'pie' || props.type === 'doughnut'
    ? colors.pie.slice(0, props.data.length)
    : props.type === 'bar'
      ? colors.bar
      : colors.line

  return {
    labels: props.labels,
    datasets: [{
      label: 'Data',
      data: props.data,
      backgroundColor,
      borderColor,
      borderWidth: 2,
      fill: props.type === 'line',
      tension: props.type === 'line' ? 0.4 : 0
    }]
  }
}

const createChart = async () => {
  if (!chartCanvas.value) return

  // Destroy existing chart
  if (chartInstance) {
    chartInstance.destroy()
    chartInstance = null
  }

  try {
    // Dynamically import Chart.js to avoid SSR issues
    const { Chart, registerables } = await import('chart.js')
    Chart.register(...registerables)

    const ctx = chartCanvas.value.getContext('2d')
    
    const mergedOptions = {
      ...defaultOptions,
      ...props.options
    }

    // Remove scales for pie/doughnut charts
    if (props.type === 'pie' || props.type === 'doughnut') {
      delete mergedOptions.scales
    }

    chartInstance = new Chart(ctx, {
      type: props.type,
      data: getChartData(),
      options: mergedOptions
    })
  } catch (error) {
    console.error('Failed to create chart:', error)
    // Fallback to simple visualization
    createFallbackChart()
  }
}

const createFallbackChart = () => {
  if (!chartCanvas.value) return

  const ctx = chartCanvas.value.getContext('2d')
  const canvas = chartCanvas.value
  
  // Clear canvas
  ctx.clearRect(0, 0, canvas.width, canvas.height)
  
  // Set canvas size
  canvas.width = canvas.offsetWidth
  canvas.height = canvas.offsetHeight
  
  // Draw simple bar chart as fallback
  const padding = 40
  const chartWidth = canvas.width - (padding * 2)
  const chartHeight = canvas.height - (padding * 2)
  
  const maxValue = Math.max(...props.data)
  const barWidth = chartWidth / props.data.length
  
  // Draw bars
  props.data.forEach((value, index) => {
    const barHeight = (value / maxValue) * chartHeight
    const x = padding + (index * barWidth)
    const y = canvas.height - padding - barHeight
    
    ctx.fillStyle = `hsl(${index * 60}, 70%, 50%)`
    ctx.fillRect(x + 5, y, barWidth - 10, barHeight)
    
    // Draw labels
    ctx.fillStyle = '#000'
    ctx.font = '12px Arial'
    ctx.textAlign = 'center'
    ctx.fillText(props.labels[index] || '', x + barWidth/2, canvas.height - 10)
    ctx.fillText(value.toString(), x + barWidth/2, y - 5)
  })
}

// Watch for prop changes
watch([() => props.data, () => props.labels, () => props.type], () => {
  nextTick(() => {
    createChart()
  })
}, { deep: true })

onMounted(() => {
  nextTick(() => {
    createChart()
  })
})
</script>

<style scoped>
.chart-container {
  position: relative;
  min-height: 300px;
}
</style>