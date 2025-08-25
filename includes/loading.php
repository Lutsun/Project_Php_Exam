<!-- includes/loading.php -->
<div id="loading-overlay" style="
    position: fixed;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    background: #121212;
    display: flex;
    flex-direction: column;
    justify-content: center;
    align-items: center;
    z-index: 9999;
    opacity: 1;
    transition: opacity 0.3s ease-out;
">
    <div style="
        font-size: 4rem;
        font-weight: 700;
        margin-bottom: 1.5rem;
        opacity: 0;
        animation: fadeIn 0.8s ease-out  forwards;
    ">
        <span style="color: white;">Stock</span>
        <span style="color: var(--primary);">Nova</span>
    </div>
    <div style="
        width: 200px;
        height: 3px;
        background: rgba(255,255,255,0.1);
        overflow: hidden;
    ">
        <div style="
            height: 100%;
            width: 0;
            background:var(--primary);
            animation: loading 0.4s ease-out forwards;
        "></div>
    </div>
</div>

<script>
// Version ultra-optimisée
document.addEventListener('DOMContentLoaded', function() {
    // Cache après 700ms max
    setTimeout(() => {
        document.getElementById('loading-overlay').style.opacity = '0';
        setTimeout(() => {
            document.getElementById('loading-overlay').remove();
        }, 300);
    }, 700);
    
    // Active l'animation immédiatement
    const style = document.createElement('style');
    style.textContent = `
        @keyframes fadeIn { to { opacity: 1; } }
        @keyframes loading { to { width: 100%; } }
    `;
    document.head.appendChild(style);
});
</script>