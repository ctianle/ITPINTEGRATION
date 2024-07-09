function setActiveNavItem(currentPath) {
    const navLinks = document.querySelectorAll('.nav-link');
    navLinks.forEach(navLink => {
        const href = navLink.getAttribute('href').trim();
        if (href === currentPath) {
            navLink.closest('.nav-item').classList.add('active');
        }
    });
}
// Call the function with the current URL path
setActiveNavItem(window.location.pathname.replace(/[^\w.]/g, ''));
