document.addEventListener("DOMContentLoaded", () => {
    // 1. Avisar al CSS que JS sí cargó correctamente
    document.body.classList.add("js-ready");

    // 2. Efecto de sombra en la barra de navegación al hacer scroll
    const navbar = document.querySelector(".navbar-mana");
    if (navbar) {
        window.addEventListener("scroll", () => {
            if (window.scrollY > 40) {
                navbar.classList.add("scrolled");
            } else {
                navbar.classList.remove("scrolled");
            }
        });
    }

    // 3. Animación Scroll Reveal para tarjetas y secciones
    const reveals = document.querySelectorAll(".reveal");
    const observer = new IntersectionObserver((entries) => {
        entries.forEach((entry) => {
            if (entry.isIntersecting) {
                entry.target.classList.add("active");
                observer.unobserve(entry.target);
            }
        });
    }, { threshold: 0.05 });

    reveals.forEach((el) => observer.observe(el));

    // 4. Si el usuario hace clic en el botón "Tecnología" o entra con #caracteristicas, mostrar de inmediato
    document.querySelectorAll('a[href="#caracteristicas"]').forEach((link) => {
        link.addEventListener("click", () => {
            reveals.forEach((el) => el.classList.add("active"));
        });
    });

    if (window.location.hash === "#caracteristicas") {
        reveals.forEach((el) => el.classList.add("active"));
    }
});