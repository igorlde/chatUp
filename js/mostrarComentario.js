document.addEventListener('DOMContentLoaded', function() {
    // Seleciona todos os botões que controlam os comentários
    const btnToggleList = document.querySelectorAll('.btn-toggle');

    btnToggleList.forEach(function(btnToggle) {
        btnToggle.addEventListener('click', function() {
            // Obtém o ID do post a partir do atributo data-post
            const postId = btnToggle.getAttribute('data-post');
            // Seleciona o container de comentários correspondente
            const comentariosContainer = document.getElementById('comentarios-container-' + postId);

            // Alterna a exibição do container dos comentários
            if (comentariosContainer.style.display === "none" || comentariosContainer.style.display === "") {
                comentariosContainer.style.display = "block";
                btnToggle.textContent = "Ocultar comentários";
            } else {
                comentariosContainer.style.display = "none";
                btnToggle.textContent = "Mostrar comentários";
            }
        });
    });
});