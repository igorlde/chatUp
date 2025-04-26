 // Rolagem automática para o post após exclusão
 document.addEventListener('DOMContentLoaded', function() {
    const urlParams = new URLSearchParams(window.location.search);
    const postId = urlParams.get('post_id');

    if (postId) {
        const postElement = document.getElementById(`post-${postId}`);
        if (postElement) {
            postElement.scrollIntoView({
                behavior: 'smooth'
            });

            // Destacar o post
            postElement.style.transition = 'all 0.5s';
            postElement.style.boxShadow = '0 0 15px rgba(52, 152, 219, 0.5)';

            setTimeout(() => {
                postElement.style.boxShadow = 'none';
            }, 2000);
        }
    }
});