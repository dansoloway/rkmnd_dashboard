/**
 * TuneUp Fitness AI Portal - Custom JavaScript
 * 
 * This file contains custom JavaScript for enhanced interactivity.
 * Alpine.js (loaded via CDN) handles most interactive components.
 */

document.addEventListener('DOMContentLoaded', function() {
    console.log('TuneUp Fitness AI Portal loaded');
    
    // Initialize any custom functionality here
    
    // Example: Auto-hide flash messages after 5 seconds
    const flashMessages = document.querySelectorAll('.flash-message');
    flashMessages.forEach(function(message) {
        setTimeout(function() {
            message.style.transition = 'opacity 0.5s';
            message.style.opacity = '0';
            setTimeout(function() {
                message.remove();
            }, 500);
        }, 5000);
    });
    
    // Example: Smooth scroll for anchor links
    document.querySelectorAll('a[href^="#"]').forEach(anchor => {
        anchor.addEventListener('click', function (e) {
            const target = document.querySelector(this.getAttribute('href'));
            if (target) {
                e.preventDefault();
                target.scrollIntoView({
                    behavior: 'smooth'
                });
            }
        });
    });
});

// Audio player helper functions
function playAudioPreview(audioUrl) {
    const audio = new Audio(audioUrl);
    audio.play();
    return audio;
}

function formatDuration(seconds) {
    const minutes = Math.floor(seconds / 60);
    const remainingSeconds = seconds % 60;
    return `${minutes}:${remainingSeconds.toString().padStart(2, '0')}`;
}

// Add your custom functions below

