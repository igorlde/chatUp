
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Menu Lateral</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.7.2/css/all.min.css" integrity="sha512-Evv84Mr4kqVGRNSgIGL/F/aIDqQb7xQ2vcrdIwxfjThSH8CSR7PBEakCr51Ck+w+/U6swU2Im1vVX0SVk9ABhg==" crossorigin="anonymous" referrerpolicy="no-referrer" />
    <link rel="stylesheet" href="newsidebar.css"> 
</head>
<body>
    <nav id="sidebar">
        <div id="sidebar-content">
            
        <!-- Foto de Perfil 
         (aqui peguei uma foto do google pq nao sei configurar pra puxar do banco de dados ;D )--> 
        
        <div id="user">
        <img src="https://i.pinimg.com/236x/a7/d3/9e/a7d39eb1998731d8c45b9a72a376f884.jpg" id="user-avatar" alt="Avatar">

        <!-- Nome do usuario -->
        <p id="user-infos">
            <span class="item-description">
                Fulana
            </span>

            <!-- Descrição -->
            <span class="item-description">
                Descrição
            </span>
        </p>
        </div>

        <ul id="side-items">

            <!-- Botao criar novo post -->
            <li class="side-item active">
                <a href="#">
                <i class="fa-solid fa-plus"></i>
                <span class="item-description">
                    Novo post
                </span>
                </a>
            </li>

            <!-- Botao buscar users -->
            <li class="side-item">
                <a href="#">
                <i class="fa-solid fa-users"></i>
                <span class="item-description">
                    Buscar Usuários
                </span>
                </a>
            </li>

            <!-- Botao buscar post -->
            <li class="side-item">
                <a href="#">
                <i class="fa-solid fa-image"></i>
                <span class="item-description">
                    Buscar Post
                </span>
                </a>
            </li>

            <!-- Botao bate papo -->
            <li class="side-item">
                <a href="#">
                <i class="fa-solid fa-comments"></i>
                <span class="item-description">
                    Bate-papo
                </span>
                </a>
            </li>

            <!-- Botao bloco de notas -->
            <li class="side-item">
                <a href="#">
                <i class="fa-solid fa-table-list"></i>
                <span class="item-description">
                    Bloco de notas
                </span>
                </a>
            </li>

            <!-- Botao configurações -->
            <li class="side-item">
                <a href="#">
                <i class="fa-solid fa-gear"></i>
                <span class="item-description">
                    Configurações
                </span>
                </a>
            </li>
        </ul>

        <!-- Setinha para abrir e fechar menu -->
        <button id="open-btn">
        <i id="open-btn-icon" class="fa-solid fa-chevron-right"></i>
        </button>
    
    </div>

    <!-- Botao para sair da conta -->
    <div id="logout">
        <button id="logout-btn">
            <i class="fa-solid fa-right-from-bracket"></i>
            <span class="item-description">
                Sair
            </span>
        </button>
    </div>
    </nav>

    <main>
        <h1> Titulo </h1>
        <img src="https://blog.matsudapet.com.br/wp-content/uploads/2024/04/maiores-cuidados-gato-filhote.jpg" width="300px">
        <h3>Que gatinho fofo :3 </h3>
    </main>

    <script src="script.js"> </script>
    
</body>
</html>