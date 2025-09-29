// QueueAI System JavaScript
document.addEventListener('DOMContentLoaded', function() {
    // Auto-refresh queue position every 30 seconds
    setInterval(function() {
        if (document.querySelector('.queue-position')) {
            location.reload();
        }
    }, 30000);

    // Enhanced form validation
    const forms = document.querySelectorAll('form');
    forms.forEach(function(form) {
        form.addEventListener('submit', function(e) {
            const submitBtn = form.querySelector('button[type="submit"]');
            if (submitBtn) {
                submitBtn.disabled = true;
                submitBtn.innerHTML = '<i class="fa fa-spinner fa-spin"></i> Processing...';
            }
        });
    });

    // Provider selection helper
    const providerSelect = document.getElementById('provider');
    const modelNameInput = document.getElementById('model_name');
    
    if (providerSelect && modelNameInput) {
        providerSelect.addEventListener('change', function() {
            const suggestions = {
                'openai': 'gpt-4o',
                'claude': 'claude-3-sonnet-20240229',
                'deepseek': 'deepseek-coder',
                'gemini': 'gemini-pro',
                'groq': 'llama3-70b-8192'
            };
            
            if (suggestions[this.value]) {
                modelNameInput.value = suggestions[this.value];
            }
        });
    }

    // Code syntax highlighting (basic)
    const codeBlocks = document.querySelectorAll('pre code');
    codeBlocks.forEach(function(block) {
        block.style.whiteSpace = 'pre-wrap';
        block.style.wordBreak = 'break-word';
    });
});

// Utility functions
function showNotification(message, type = 'info') {
    const alertClass = type === 'error' ? 'alert-danger' : 'alert-success';
    const notification = document.createElement('div');
    notification.className = `alert ${alertClass} alert-dismissible`;
    notification.innerHTML = `
        <button type="button" class="close" data-dismiss="alert">&times;</button>
        ${message}
    `;
    
    const container = document.querySelector('.content-wrapper');
    if (container) {
        container.insertBefore(notification, container.firstChild);
        
        setTimeout(function() {
            notification.remove();
        }, 5000);
    }
}

function formatCode(code, language) {
    // Basic code formatting
    return code.replace(/\t/g, '    '); // Convert tabs to spaces
}