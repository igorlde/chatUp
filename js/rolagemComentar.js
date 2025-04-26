document.addEventListener('DOMContentLoaded', function() {
    const urlParams = new URLSearchParams(window.location.search);

    // Scroll para comentários se houver parâmetro
    if (urlParams.has('comentario')) {
        const postId = urlParams.get('post_id');
        const comentarioStatus = document.getElementById('comentario-status');

        if (postId) {
            const targetSection = document.getElementById(`comentarios-post-${postId}`);
            if (targetSection) {
                setTimeout(() => {
                    targetSection.scrollIntoView({
                        behavior: 'smooth',
                        block: 'start'
                    });
                }, 500);
            }
        }

        // Remove a notificação após 5 segundos
        if (comentarioStatus) {
            setTimeout(() => {
                comentarioStatus.style.transform = 'translateX(150%)';
                setTimeout(() => comentarioStatus.remove(), 500);
            }, 5000);
        }
    }
});