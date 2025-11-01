// Wait for the page to be fully loaded
document.addEventListener('DOMContentLoaded', function() {
    
    // Find all the toggle buttons on the page
    const toggleButtons = document.querySelectorAll('.toggle-password');
    
    toggleButtons.forEach(function(button) {
        button.addEventListener('click', function() {
            
            // Find the password input field that is the 'previous sibling' 
            // (the element right before) this button
            const inputField = this.previousElementSibling;
            
            if (inputField && inputField.tagName === 'INPUT') {
                
                // Check the current type of the input
                const isPassword = inputField.type === 'password';
                
                // Change the type and the icon
                if (isPassword) {
                    inputField.type = 'text';
                    this.classList.remove('fa-eye');
                    this.classList.add('fa-eye-slash');
                } else {
                    inputField.type = 'password';
                    this.classList.remove('fa-eye-slash');
                    this.classList.add('fa-eye');
                }
            }
        });
    });
});