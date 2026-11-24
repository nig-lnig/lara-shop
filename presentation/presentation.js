/*
 * Lara-Shop — diaporama. No library, works from file:// (double-click index.html).
 *
 * Keys:  → / Space / PageDown : next      ← / PageUp : previous
 *        Home / End : first / last        N : speaker notes      F : full screen
 *        R : reveal the quiz answers of the current slide
 * The slide number is kept in the address (#12), so a refresh stays on the slide.
 */
document.documentElement.classList.add('js');

document.addEventListener('DOMContentLoaded', () => {
    const deck = document.querySelector('.deck');
    const slides = [...document.querySelectorAll('.slide')];
    const viewport = document.querySelector('.viewport');
    const counter = document.querySelector('[data-counter]');
    const label = document.querySelector('[data-section-label]');
    const progress = document.querySelector('.progress');
    const prevBtn = document.querySelector('[data-prev]');
    const nextBtn = document.querySelector('[data-next]');
    const notesBtn = document.querySelector('[data-notes]');
    const notesPanel = document.querySelector('.notes-panel');
    let current = 0;

    // Fill the footer of each slide (section, presenter, time, number).
    slides.forEach((slide, i) => {
        const footer = document.createElement('div');
        footer.className = 'slide-footer';
        footer.innerHTML = `<span class="brand"><span class="brand-mark" aria-hidden="true">L</span> Lara-Shop</span>
            <span>Partie ${slide.dataset.presenter} / 19 · ${slide.dataset.section}</span>
            <span>${i + 1} / ${slides.length}</span>`;
        slide.append(footer);
        slide.setAttribute('aria-label', `Diapositive ${i + 1} sur ${slides.length}`);
    });

    // Scale the 1280 x 720 canvas to the available space.
    const fit = () => {
        const scale = Math.min(viewport.clientWidth / 1280, viewport.clientHeight / 720) * 0.97;
        deck.style.transform = `translate(-50%, -50%) scale(${scale})`;
    };

    const notesHtml = (slide) => {
        const notes = slide.querySelector('.notes');
        const nextSlide = slides[slides.indexOf(slide) + 1];
        const nextWho = nextSlide && nextSlide.dataset.presenter !== slide.dataset.presenter
            ? `<p class="next">Ensuite : partie ${nextSlide.dataset.presenter}, ${nextSlide.dataset.section}</p>` : '';
        return `<h2>Notes de l’orateur</h2>
            <div class="who">Partie ${slide.dataset.presenter} · ${slide.dataset.section}</div>
            <span class="time">Temps de parole de la partie : ${slide.dataset.time}</span>
            ${notes ? notes.innerHTML : '<p>(pas de note)</p>'}${nextWho}`;
    };

    const show = (index) => {
        current = Math.max(0, Math.min(slides.length - 1, index));
        slides.forEach((slide, i) => {
            slide.classList.toggle('is-active', i === current);
            slide.setAttribute('aria-hidden', i === current ? 'false' : 'true');
            slide.inert = i !== current; // buttons of hidden slides cannot be reached with Tab
        });
        const slide = slides[current];
        counter.innerHTML = `<b>${current + 1}</b> / ${slides.length}`;
        label.textContent = `Partie ${slide.dataset.presenter} · ${slide.dataset.section}`;
        progress.style.width = `${((current + 1) / slides.length) * 100}%`;
        prevBtn.disabled = current === 0;
        nextBtn.disabled = current === slides.length - 1;
        notesPanel.innerHTML = notesHtml(slide);
        if (location.hash !== `#${current + 1}`) history.replaceState(null, '', `#${current + 1}`);
    };

    const revealAll = () => slides[current].querySelectorAll('.q').forEach((q) => q.classList.add('revealed'));

    prevBtn.addEventListener('click', () => show(current - 1));
    nextBtn.addEventListener('click', () => show(current + 1));
    notesBtn.addEventListener('click', () => {
        const on = document.body.classList.toggle('show-notes');
        notesBtn.setAttribute('aria-pressed', String(on));
        setTimeout(fit, 260);
    });
    document.querySelector('[data-fullscreen]').addEventListener('click', () => {
        if (document.fullscreenElement) document.exitFullscreen(); else document.documentElement.requestFullscreen?.();
    });

    // Quiz: each "Afficher la réponse" button reveals its own answer.
    document.querySelectorAll('.q .reveal').forEach((button) => {
        button.addEventListener('click', () => button.closest('.q').classList.add('revealed'));
    });

    document.addEventListener('keydown', (event) => {
        if (event.target.closest('button') && (event.key === ' ' || event.key === 'Enter')) return; // let buttons work
        const key = event.key;
        if (['ArrowRight', 'PageDown', ' '].includes(key)) { event.preventDefault(); show(current + 1); }
        else if (['ArrowLeft', 'PageUp'].includes(key)) { event.preventDefault(); show(current - 1); }
        else if (key === 'Home') show(0);
        else if (key === 'End') show(slides.length - 1);
        else if (key === 'n' || key === 'N') notesBtn.click();
        else if (key === 'f' || key === 'F') document.querySelector('[data-fullscreen]').click();
        else if (key === 'r' || key === 'R') revealAll();
    });

    window.addEventListener('resize', fit);
    window.addEventListener('hashchange', () => show(Number(location.hash.slice(1)) - 1 || 0));
    fit();
    show(Number(location.hash.slice(1)) - 1 || 0);
});
