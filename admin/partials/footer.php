</main>
</div>
<script>
// Sidebar toggle script for mobile
document.getElementById('menu-btn').addEventListener('click', function() {
    document.getElementById('sidebar').classList.toggle('-translate-x-full');
});

// Enhanced theme toggle switch logic (from your original index.php)
(function() {
    const themeToggle = document.getElementById('themeToggle');
    const themeThumb = document.getElementById('themeThumb');
    const iconDark = document.getElementById('themeIconDark');
    const iconLight = document.getElementById('themeIconLight');

    function setTheme(theme) {
        document.documentElement.setAttribute('data-theme', theme);
        localStorage.setItem('theme', theme);
        if (theme === 'dark') {
            themeThumb.style.transform = 'translateX(0)';
            iconDark.style.opacity = '1';
            iconLight.style.opacity = '0';
            document.getElementById('bg-dark').style.display = '';
            document.getElementById('bg-light').style.display = 'none';
        } else {
            themeThumb.style.transform = 'translateX(16px)';
            iconDark.style.opacity = '0';
            iconLight.style.opacity = '1';
            document.getElementById('bg-dark').style.display = 'none';
            document.getElementById('bg-light').style.display = '';
        }
    }

    function getTheme() {
        return localStorage.getItem('theme') || 'dark';
    }
    setTheme(getTheme());
    themeToggle.addEventListener('click', () => {
        const current = getTheme();
        setTheme(current === 'dark' ? 'light' : 'dark');
    });
})();
</script>
</body>

</html>