// This file contains the JavaScript functionality for the application. 

document.addEventListener('DOMContentLoaded', function() {
    // Example: Handle form submissions for creating questions and answers
    const questionForm = document.getElementById('questionForm');
    const answerForm = document.getElementById('answerForm');

    if (questionForm) {
        questionForm.addEventListener('submit', function(event) {
            event.preventDefault();
            // Add logic to handle question submission
            const formData = new FormData(questionForm);
            fetch('/submit-question', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                // Handle success or error
                console.log(data);
            })
            .catch(error => console.error('Error:', error));
        });
    }

    if (answerForm) {
        answerForm.addEventListener('submit', function(event) {
            event.preventDefault();
            // Add logic to handle answer submission
            const formData = new FormData(answerForm);
            fetch('/submit-answer', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                // Handle success or error
                console.log(data);
            })
            .catch(error => console.error('Error:', error));
        });
    }
});