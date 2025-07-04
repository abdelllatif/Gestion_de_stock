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
