<nav class="nav-busca">
    <a href="main.php" class="btn-voltar">
        <i class="fas fa-arrow-left"></i> Voltar
    </a>
    
    <form method="POST" action="busca.php" class="form-busca">
        <input type="search"
               name="User_name"
               value="<?= htmlspecialchars($_POST['User_name'] ?? '') ?>"
               placeholder="Buscar usuários..."
               class="input-busca">
        <button type="submit" class="btn-busca">
            <i class="fas fa-search"></i>
        </button>
    </form>
</nav>
