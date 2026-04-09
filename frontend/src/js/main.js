import '../assets/style.css';
import { createIcons } from 'lucide';

console.log('ReformAí Frontend Loaded Successfully');

const initApp = () => {
  createIcons(); // Instansiate Lucide icons

  const sections = document.querySelectorAll('section[id]');
  const navLinks = document.querySelectorAll('.nav-link');

  if (sections.length > 0 && navLinks.length > 0) {
    const observerOptions = {
      root: null,
      rootMargin: '-20% 0px -70% 0px', // Trigger precisely when section hits top/middle
      threshold: 0
    };

    const observer = new IntersectionObserver((entries) => {
      entries.forEach(entry => {
        if (entry.isIntersecting) {
          navLinks.forEach(link => {
            link.setAttribute('data-active', 'false');
            // Match the section id with the link href
            if (link.getAttribute('href') === `#${entry.target.id}`) {
              link.setAttribute('data-active', 'true');
            }
          });
        }
      });
    }, observerOptions);

    sections.forEach(section => observer.observe(section));
  }

  // Tabs Logic for "Como Funciona" - USING EVENT DELEGATION
  const activeClasses = ['text-orange-500', 'border-orange-500'];
  const inactiveClasses = ['text-slate-400', 'border-transparent', 'hover:text-slate-900'];

  const setTabState = (isActive, tabElement) => {
    if (!tabElement) return;
    if (isActive) {
      tabElement.classList.remove(...inactiveClasses);
      tabElement.classList.add(...activeClasses);
    } else {
      tabElement.classList.remove(...activeClasses);
      tabElement.classList.add(...inactiveClasses);
    }
  };

  document.addEventListener('click', (e) => {
    const isCliente = e.target.closest('#tab-cliente');
    const isPrestador = e.target.closest('#tab-prestador');

    if (isCliente || isPrestador) {
      const tCliente = document.getElementById('tab-cliente');
      const tPrestador = document.getElementById('tab-prestador');
      const cCliente = document.getElementById('content-cliente');
      const cPrestador = document.getElementById('content-prestador');

      if (!cCliente || !cPrestador) return;

      if (isCliente) {
        cPrestador.classList.add('hidden');
        cCliente.classList.remove('hidden');
        setTabState(true, tCliente);
        setTabState(false, tPrestador);
      }

      if (isPrestador) {
        cCliente.classList.add('hidden');
        cPrestador.classList.remove('hidden');
        setTabState(true, tPrestador);
        setTabState(false, tCliente);
        
        // Re-initialize icons just in case
        createIcons();
      }
    }
  });
};

if (document.readyState === 'loading') {
  document.addEventListener('DOMContentLoaded', initApp);
} else {
  initApp();
}
