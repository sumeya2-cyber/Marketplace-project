// Simple toggle for search box or other animations (if needed)
function togglePostForm() {
    const formDiv = document.getElementById('postForm');
    if (formDiv.style.display === 'none' || formDiv.style.display === '') {
        formDiv.style.display = 'block';
    } else {
        formDiv.style.display = 'none';
    }
}

// Basic form validation
function validateForm() {
    const form = document.getElementById('itemForm');
    if (!form.title.value.trim() || !form.description.value.trim() || !form.price.value || !form.category_id.value) {
        alert('Please fill all required fields');
        return false;
    }
    // Add animations or transitions if needed
    return true;
}