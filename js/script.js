
// Function to open the modal when the user clicks on the "Create Client Account" button
function openModal(modalId) {
    document.getElementById(modalId).classList.remove('hidden');
}

// Function to close the modal when the user clicks on the "Close" button
function closeModal(modalId) {
    document.getElementById(modalId).classList.add('hidden');
}


// Function to show the selected form based on the role selected in the register.php
function showForm(type) {
    // Hide both forms
    document.getElementById('employeeForm').classList.add('hidden');
    document.getElementById('clientForm').classList.add('hidden');
    
    // Show selected form
    if (type === 'employee') {
        document.getElementById('employeeForm').classList.remove('hidden');
    } else if (type === 'client') {
        document.getElementById('clientForm').classList.remove('hidden');
    }
}