window.addEventListener('DOMContentLoaded', function() {
  let btn_passview = document.getElementById("btn_passview");
  let input_pass = document.getElementById("input_pass");

  btn_passview.addEventListener("click", () => {
    if (input_pass.type === 'password') {
      input_pass.type = 'text';
      btn_passview.classList.remove("fa-eye");
      btn_passview.classList.add("fa-eye-slash");
    } else {
      input_pass.type = 'password';
      btn_passview.classList.remove("fa-eye-slash");
      btn_passview.classList.add("fa-eye");
    }
  });
});