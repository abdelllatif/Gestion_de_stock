  function showSuccess(message) {
    Swal.fire({
      title: message,
      icon: 'success',
      confirmButtonColor: '#3085d6',
      confirmButtonText: 'OK'
    });
  }

  function showError(message) {
    Swal.fire({
      title: message,
      icon: 'error',
      confirmButtonColor: '#d33',
      confirmButtonText: 'OK'
    });
  }

  function showWarning(message) {
    Swal.fire({
      title: message,
      icon: 'warning',
      confirmButtonColor: '#f39c12',
      confirmButtonText: 'OK'
    });
  }

  function showConfirm(message, confirmText = 'Yes', cancelText = 'Cancel') {
    return Swal.fire({
      title: message,
      icon: 'question',
      showCancelButton: true,
      confirmButtonColor: '#3085d6',
      cancelButtonColor: '#d33',
      confirmButtonText: confirmText,
      cancelButtonText: cancelText
    });
  }

// New: Toast/queue style auto-dismiss alert (no button)
function showToast(message, type = 'success', timer = 1800) {
  Swal.fire({
    toast: true,
    position: 'top-end',
    icon: type,
    title: message,
    showConfirmButton: false,
    timer: timer,
    timerProgressBar: true
  });
}
