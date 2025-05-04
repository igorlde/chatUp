<nav>
    <a href="main.php" class="btn-voltar">← Voltar</a>
    <form method="POST" action="busca.php" class="form-busca">
        <input type="search"
            name="User_name"
            value="<?= htmlspecialchars($_POST['User_name'] ?? '') ?>"
            placeholder="Buscar usuários...">
        <button type="submit">🔍</button>
    </form>
</nav>