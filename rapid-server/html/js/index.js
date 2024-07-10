function setActiveNavItem(currentPath) {
    const navLinks = document.querySelectorAll('.nav-link');
    
    navLinks.forEach(navLink => {
        const href = navLink.getAttribute('href').trim();
        const hrefBase = href.replace('.php', ''); // Remove .php extension for comparison

        // Specific case where 'student_overview' should activate 'sessions'
        if (currentPath === 'student_overview') {
            if (hrefBase === 'sessions') {
                navLink.closest('.nav-item').classList.add('active');
            }
            // Deactivate 'students' and 'overview'
            if (hrefBase === 'students' || hrefBase === 'overview') {
                navLink.closest('.nav-item').classList.remove('active');
            }
            return;
        }

        // Direct match check
        if (currentPath === hrefBase) {
            navLink.closest('.nav-item').classList.add('active');
            return;
        }

        // Check if any substring of 5 consecutive characters in currentPath matches hrefBase
        let matchFound = false;
        for (let i = 0; i <= currentPath.length - 5; i++) {
            const substring = currentPath.slice(i, i + 5);
            if (hrefBase.includes(substring)) {
                matchFound = true;
                break;
            }
        }

        if (matchFound) {
            navLink.closest('.nav-item').classList.add('active');
        }
    });
}

// Call the function with the current URL path
setActiveNavItem(window.location.pathname.replace('/', '').replace('.php', ''));
