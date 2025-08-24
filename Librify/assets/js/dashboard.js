// JavaScript para el dashboard
document.addEventListener("DOMContentLoaded", () => {
  // Import Bootstrap
  const bootstrap = window.bootstrap

  // Inicializar tooltips de Bootstrap
  var tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'))
  var tooltipList = tooltipTriggerList.map((tooltipTriggerEl) => new bootstrap.Tooltip(tooltipTriggerEl))

  // Animación de las tarjetas de estadísticas
  const cards = document.querySelectorAll(".card")
  cards.forEach((card, index) => {
    card.style.animationDelay = `${index * 0.1}s`
  })

  // Actualizar estadísticas cada 30 segundos
  setInterval(updateStats, 30000)

  // Función para actualizar estadísticas
  function updateStats() {
    fetch("api/stats.php")
      .then((response) => response.json())
      .then((data) => {
        if (data.success) {
          // Actualizar los valores en las tarjetas
          updateStatCard("total-libros", data.total_libros)
          updateStatCard("total-usuarios", data.total_usuarios)
          updateStatCard("prestamos-activos", data.prestamos_activos)
          updateStatCard("prestamos-vencidos", data.prestamos_vencidos)
        }
      })
      .catch((error) => console.error("Error updating stats:", error))
  }

  // Función auxiliar para actualizar una tarjeta de estadística
  function updateStatCard(id, value) {
    const element = document.getElementById(id)
    if (element) {
      element.textContent = value
      element.classList.add("animate-pulse")
      setTimeout(() => {
        element.classList.remove("animate-pulse")
      }, 1000)
    }
  }

  // Confirmar acciones importantes
  const deleteButtons = document.querySelectorAll(".btn-delete")
  deleteButtons.forEach((button) => {
    button.addEventListener("click", (e) => {
      if (!confirm("¿Está seguro de que desea eliminar este elemento?")) {
        e.preventDefault()
      }
    })
  })

  // Auto-hide alerts después de 5 segundos
  const alerts = document.querySelectorAll(".alert")
  alerts.forEach((alert) => {
    setTimeout(() => {
      alert.style.opacity = "0"
      setTimeout(() => {
        alert.remove()
      }, 300)
    }, 5000)
  })
})

// Función para mostrar notificaciones
function showNotification(message, type = "info") {
  const alertDiv = document.createElement("div")
  alertDiv.className = `alert alert-${type} alert-dismissible fade show position-fixed`
  alertDiv.style.top = "70px"
  alertDiv.style.right = "20px"
  alertDiv.style.zIndex = "9999"
  alertDiv.innerHTML = `
        ${message}
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    `

  document.body.appendChild(alertDiv)

  // Auto-remove después de 5 segundos
  setTimeout(() => {
    alertDiv.remove()
  }, 5000)
}

// Función para validar formularios
function validateForm(formId) {
  const form = document.getElementById(formId)
  if (!form) return false

  const requiredFields = form.querySelectorAll("[required]")
  let isValid = true

  requiredFields.forEach((field) => {
    if (!field.value.trim()) {
      field.classList.add("is-invalid")
      isValid = false
    } else {
      field.classList.remove("is-invalid")
    }
  })

  return isValid
}
