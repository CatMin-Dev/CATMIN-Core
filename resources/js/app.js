import 'bootstrap';

function slugify(value) {
	return String(value || '')
		.toLowerCase()
		.normalize('NFD')
		.replace(/[\u0300-\u036f]/g, '')
		.replace(/[^a-z0-9\s-]/g, '')
		.trim()
		.replace(/\s+/g, '-');
}

function buildFloatingBookmarks() {
	const navs = document.querySelectorAll('.catmin-bookmarks-auto');
	if (!navs.length) return;

	navs.forEach((nav, index) => {
		const root = nav.closest('article, main, .container, body') || document.body;
		const headings = Array.from(root.querySelectorAll('h2, h3')).filter((el) => {
			const text = (el.textContent || '').trim();
			return text !== '' && !nav.contains(el);
		});

		const list = nav.querySelector('.catmin-bookmarks-list');
		if (!list) return;

		if (!headings.length) {
			nav.style.display = 'none';
			return;
		}

		nav.style.display = '';
		list.innerHTML = '';

		headings.forEach((heading, headingIndex) => {
			if (!heading.id) {
				heading.id = `catmin-anchor-${index}-${headingIndex}-${slugify(heading.textContent || '') || 'section'}`;
			}

			const li = document.createElement('li');
			const link = document.createElement('a');
			link.className = 'catmin-bookmarks-link';
			link.href = `#${heading.id}`;
			link.textContent = (heading.textContent || '').trim();
			li.appendChild(link);
			list.appendChild(li);
		});

		const links = Array.from(list.querySelectorAll('.catmin-bookmarks-link'));
		const observer = new IntersectionObserver((entries) => {
			entries.forEach((entry) => {
				if (!entry.isIntersecting) return;
				const id = entry.target.getAttribute('id');
				links.forEach((link) => {
					const active = link.getAttribute('href') === `#${id}`;
					link.classList.toggle('is-active', active);
				});
			});
		}, { rootMargin: '0px 0px -65% 0px', threshold: 0.15 });

		headings.forEach((heading) => observer.observe(heading));
	});
}

document.addEventListener('DOMContentLoaded', () => {
	buildFloatingBookmarks();
});
