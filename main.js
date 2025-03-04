// Function to show the selected section and hide others
function showSection(sectionId) {
    // Get all sections
    const sections = document.querySelectorAll('.section');
    
    // Loop through sections and hide them
    sections.forEach(section => {
        if (section.id === sectionId) {
            section.classList.remove('hidden'); // Show the selected section
        } else {
            section.classList.add('hidden'); // Hide other sections
        }
    });
}

// Add event listeners to navigation buttons
document.querySelectorAll('.dashboard-btn').forEach(button => {
    button.addEventListener('click', () => {
        const sectionId = button.getAttribute('onclick').match(/'(.*?)'/)[1];
        showSection(sectionId);
    });
});
