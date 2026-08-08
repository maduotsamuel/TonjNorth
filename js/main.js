// JavaScript for Interactivity
        const newsGrid = document.getElementById('news-grid');
        const newsStatus = document.getElementById('news-status');
        const galleryTrack = document.getElementById('gallery-track');
        const galleryDots = document.getElementById('gallery-dots');
        const galleryPrev = document.getElementById('gallery-prev');
        const galleryNext = document.getElementById('gallery-next');
        const matchesList = document.getElementById('matches-list');
        const standingsTable = document.getElementById('standings-table');

        const fallbackNews = [
            {
                title: 'Season preparations underway',
                excerpt: 'Training intensity is rising as the squad sharpens its rhythm ahead of the next tournament.',
                body: 'The squad has begun a focused preparation phase with extra training sessions, stronger tactical work, and renewed discipline ahead of the next competition.',
                publishedAt: '2026-08-06T10:30:00Z',
                image: 'img/hero.jpeg'
            },
            {
                title: 'Community outreach expands across Tonj North',
                excerpt: 'The club continues to connect with supporters through youth engagement and local football programs.',
                body: 'Community outreach is growing as the club spends more time with local youth groups, schools, and football lovers across Tonj North County.',
                publishedAt: '2026-08-03T08:15:00Z',
                image: 'img/celebration.jpeg'
            },
            {
                title: 'Fans rally behind the mighty side',
                excerpt: 'Supporters gathered to celebrate the team’s latest performances and share messages of unity.',
                body: 'A wave of encouragement from supporters has boosted morale across the club and strengthened the bond with the community.',
                publishedAt: '2026-07-29T15:45:00Z',
                image: 'img/intense.jpeg'
            }
        ];

        const fallbackGallery = [
            { title: 'Match action', caption: 'Intense match action in Tonj North', image: 'img/intense.jpeg', publishedAt: '2026-08-06T10:30:00Z' },
            { title: 'Victory celebration', caption: 'Celebrating victory with fans', image: 'img/celebration.jpeg', publishedAt: '2026-08-05T09:00:00Z' },
            { title: 'Team lineup', caption: 'Starting lineup ready for battle', image: 'img/squad.jpeg', publishedAt: '2026-08-04T11:20:00Z' }
        ];

        const fallbackMatches = [
            { homeTeam: 'Tonj North Football Team', awayTeam: 'Gogrial East Football Team', matchDate: '2026-08-09T15:00:00', venue: 'Buluk', competition: 'Semi Final', status: 'upcoming' },
            { homeTeam: 'Tonj North Football Team', awayTeam: 'Tonj South Football Team', matchDate: '2026-08-02T15:00:00', venue: 'Buluk', competition: 'League', resultHome: 1, resultAway: 0, status: 'finished' },
            { homeTeam: 'Tonj North Football Team', awayTeam: 'Twic Mayardit Football Team', matchDate: '2026-08-01T15:00:00', venue: 'Buluk', competition: 'League', resultHome: 1, resultAway: 3, status: 'finished' }
        ];

        const fallbackStandings = [
            { teamName: 'Twic Mayardit Football Team', played: 2, wins: 2, draws: 0, losses: 0, goalsFor: 5, goalsAgainst: 2, points: 6, position: 1 },
            { teamName: 'Tonj North Football Team', played: 2, wins: 1, draws: 0, losses: 1, goalsFor: 2, goalsAgainst: 3, points: 3, position: 2 },
            { teamName: 'Tonj South Football Team', played: 2, wins: 0, draws: 0, losses: 2, goalsFor: 1, goalsAgainst: 4, points: 0, position: 3 }
        ];

        function escapeHtml(value) {
            return String(value)
                .replace(/&/g, '&amp;')
                .replace(/</g, '&lt;')
                .replace(/>/g, '&gt;')
                .replace(/"/g, '&quot;')
                .replace(/'/g, '&#39;');
        }

        function getPortableText(content) {
            if (!content) return '';
            if (typeof content === 'string') return content;
            if (Array.isArray(content)) {
                return content.map((block) => {
                    if (typeof block === 'string') return block;
                    if (block?.children) {
                        return block.children.map((child) => child.text || '').join('');
                    }
                    return '';
                }).join('\n\n');
            }
            return '';
        }

        function resolveNewsImage(item, fallbackIndex = 0) {
            const fallbackImages = ['img/hero.jpeg', 'img/celebration.jpeg', 'img/intense.jpeg'];
            const candidates = [
                item?.image,
                item?.imageUrl,
                item?.mainImage,
                item?.featuredImage,
                item?.coverImage,
                item?.image?.asset?.url,
                item?.mainImage?.asset?.url,
                item?.featuredImage?.asset?.url,
                item?.coverImage?.asset?.url,
                item?.image?.asset?._ref,
                item?.mainImage?.asset?._ref,
                item?.featuredImage?.asset?._ref,
                item?.coverImage?.asset?._ref
            ];

            for (const candidate of candidates) {
                if (typeof candidate === 'string' && candidate.trim()) {
                    return candidate;
                }
            }

            return fallbackImages[fallbackIndex % fallbackImages.length];
        }

        function formatDate(value) {
            if (!value) return 'Latest update';
            return new Date(value).toLocaleDateString('en', { month: 'short', day: 'numeric', year: 'numeric' });
        }

        function openNewsModal(item) {
            const modal = document.getElementById('news-modal');
            const modalContent = document.getElementById('news-modal-content');
            if (!modal || !modalContent) return;

            const title = typeof item.title === 'string' ? item.title : item.title?.en || item.title?.[0]?.text || 'Club update';
            const body = getPortableText(item.body || item.content || item.description || item.excerpt) || (typeof item.excerpt === 'string' ? item.excerpt : item.excerpt?.en || 'More details will be shared soon.');
            const date = item.publishedAt ? new Date(item.publishedAt).toLocaleDateString('en', { month: 'long', day: 'numeric', year: 'numeric' }) : 'Latest update';

            modalContent.innerHTML = `
                <div class="space-y-5">
                    <div class="rounded-2xl border border-clubYellow/20 bg-gradient-to-br from-clubYellow/10 via-clubDark to-clubDarker p-6">
                        <p class="text-[11px] font-semibold uppercase tracking-[0.3em] text-clubYellow">${escapeHtml(date)}</p>
                        <h4 class="mt-3 text-2xl font-black text-white">${escapeHtml(title)}</h4>
                    </div>
                    <div class="prose prose-invert max-w-none text-gray-300">
                        <p class="text-base leading-8">${escapeHtml(body).replace(/\n/g, '<br><br>')}</p>
                    </div>
                    <a href="#contact" class="inline-flex items-center rounded-full border border-clubYellow/30 bg-clubYellow/10 px-5 py-2.5 text-sm font-semibold text-clubYellow transition hover:bg-clubYellow hover:text-black">
                        Contact the club <i class="fas fa-arrow-right ml-2"></i>
                    </a>
                </div>
            `;

            modal.classList.remove('hidden');
            modal.classList.add('flex');
            document.body.classList.add('overflow-hidden');
        }

        function closeNewsModal() {
            const modal = document.getElementById('news-modal');
            if (!modal) return;
            modal.classList.add('hidden');
            modal.classList.remove('flex');
            document.body.classList.remove('overflow-hidden');
        }

        function renderNews(items) {
            if (!newsGrid) return;

            if (!items || !items.length) {
                newsGrid.innerHTML = '<div class="md:col-span-2 xl:col-span-3 rounded-2xl border border-gray-800 bg-clubDarker p-8 text-center text-gray-400">No stories available right now. Please check back soon.</div>';
                return;
            }

            newsGrid.innerHTML = items.slice(0, 3).map((item, index) => {
                const title = typeof item.title === 'string' ? item.title : item.title?.en || item.title?.[0]?.text || 'Club update';
                const excerpt = typeof item.excerpt === 'string'
                    ? item.excerpt
                    : typeof item.excerpt?.en === 'string'
                        ? item.excerpt.en
                        : typeof item.excerpt?.[0]?.text === 'string'
                            ? item.excerpt[0].text
                            : typeof item.description === 'string'
                                ? item.description
                                : 'Stay tuned for more news from the club.';
                const body = getPortableText(item.body || item.content || item.description || item.excerpt);
                const date = formatDate(item.publishedAt);
                const imageUrl = resolveNewsImage(item, index);

                return `
                    <article class="group overflow-hidden rounded-2xl border border-gray-800 bg-clubDarker shadow-lg transition hover:-translate-y-1 hover:border-clubYellow/50">
                        <div class="relative overflow-hidden">
                            <img src="${escapeHtml(imageUrl)}" alt="${escapeHtml(title)}" class="news-card-image h-full w-full transition duration-500 group-hover:scale-105" loading="lazy">
                            <div class="absolute inset-0 bg-gradient-to-t from-black/80 via-black/20 to-transparent"></div>
                            <div class="absolute bottom-4 left-4 rounded-full border border-clubYellow/30 bg-black/40 px-3 py-1 text-[11px] font-semibold uppercase tracking-[0.25em] text-clubYellow">
                                Club Update
                            </div>
                        </div>
                        <div class="p-6">
                            <p class="text-[11px] font-semibold uppercase tracking-[0.25em] text-gray-500">${escapeHtml(date)}</p>
                            <h3 class="mt-3 text-xl font-black text-white">${escapeHtml(title)}</h3>
                            <p class="mt-3 text-sm leading-6 text-gray-400">${escapeHtml(excerpt)}</p>
                            <button type="button" data-news-index="${index}" data-news-body="${escapeHtml(body)}" class="news-read-more mt-5 inline-flex items-center text-sm font-semibold text-clubYellow hover:text-yellow-400">
                                Read more <i class="fas fa-arrow-right ml-2"></i>
                            </button>
                        </div>
                    </article>
                `;
            }).join('');

            document.querySelectorAll('.news-read-more').forEach((button) => {
                button.addEventListener('click', () => {
                    const item = items[Number(button.getAttribute('data-news-index'))];
                    if (item) {
                        const body = button.getAttribute('data-news-body') || '';
                        const detailItem = { ...item, body: body || item.body || item.content || item.description || item.excerpt };
                        openNewsModal(detailItem);
                    }
                });
            });
        }

        async function loadNews() {
            renderNews(fallbackNews);
            if (newsStatus) {
                newsStatus.textContent = 'Loading content from the PHP CMS...';
            }

            try {
                const response = await fetch('php/api.php?action=news');
                const result = await response.json();
                if (response.ok && result?.items?.length) {
                    renderNews(result.items);
                    if (newsStatus) {
                        newsStatus.textContent = 'Latest stories synced from the CMS.';
                    }
                } else {
                    if (newsStatus) {
                        newsStatus.textContent = 'Showing the local preview while the CMS is still being populated.';
                    }
                }
            } catch (error) {
                if (newsStatus) {
                    newsStatus.textContent = 'Unable to reach the PHP CMS right now, so the local preview is being shown.';
                }
            }
        }

        function renderGallery(items) {
            if (!galleryTrack) return;
            const container = galleryTrack.querySelector('.flex');
            if (!container) return;

            if (!items || !items.length) {
                container.innerHTML = '<div class="w-full rounded-2xl border border-gray-800 bg-clubDarker p-8 text-center text-gray-400">No gallery moments available yet.</div>';
                return;
            }

            container.innerHTML = items.map((item) => `
                <div class="gallery-card w-full sm:w-[calc(50%-0.75rem)] lg:w-[calc(33.333%-1rem)] px-1 sm:px-2">
                    <div class="relative group overflow-hidden rounded-2xl h-64 border border-gray-800">
                        <img src="${escapeHtml(item.image || 'img/hero.jpeg')}" alt="${escapeHtml(item.title || 'Club gallery')}" class="w-full h-full object-cover object-center group-hover:scale-110 transition duration-500">
                        <div class="absolute inset-0 bg-gradient-to-t from-black/80 via-transparent opacity-0 group-hover:opacity-100 transition flex items-end p-6">
                            <span class="text-white font-bold text-sm">${escapeHtml(item.caption || item.title || 'Club gallery')}</span>
                        </div>
                    </div>
                </div>
            `).join('');

            if (galleryDots) {
                galleryDots.innerHTML = '';
                const cards = container.querySelectorAll('.gallery-card');
                cards.forEach((_, index) => {
                    const dot = document.createElement('button');
                    dot.type = 'button';
                    dot.className = `gallery-dot h-2.5 w-2.5 rounded-full transition ${index === 0 ? 'bg-clubYellow' : 'bg-gray-600'}`;
                    dot.setAttribute('aria-label', `Go to gallery image ${index + 1}`);
                    dot.addEventListener('click', () => updateGallery(index));
                    galleryDots.appendChild(dot);
                });
            }

            updateGallery(0);
        }

        let galleryIndex = 0;
        let galleryStartX = 0;

        function updateGallery(index) {
            if (!galleryTrack) return;
            const container = galleryTrack.querySelector('.flex');
            const cards = container?.querySelectorAll('.gallery-card') || [];
            if (!cards.length) return;

            galleryIndex = (index + cards.length) % cards.length;
            cards[galleryIndex].scrollIntoView({ behavior: 'smooth', block: 'nearest', inline: 'start' });

            if (galleryDots) {
                const dots = galleryDots.querySelectorAll('.gallery-dot');
                dots.forEach((dot, dotIndex) => {
                    dot.className = `gallery-dot h-2.5 w-2.5 rounded-full transition ${dotIndex === galleryIndex ? 'bg-clubYellow' : 'bg-gray-600'}`;
                });
            }
        }

        if (galleryTrack && galleryPrev && galleryNext) {
            galleryPrev.addEventListener('click', () => updateGallery(galleryIndex - 1));
            galleryNext.addEventListener('click', () => updateGallery(galleryIndex + 1));
            galleryTrack.addEventListener('touchstart', (event) => {
                galleryStartX = event.touches[0].clientX;
            });
            galleryTrack.addEventListener('touchend', (event) => {
                const endX = event.changedTouches[0].clientX;
                if (galleryStartX - endX > 50) {
                    updateGallery(galleryIndex + 1);
                } else if (endX - galleryStartX > 50) {
                    updateGallery(galleryIndex - 1);
                }
            });
        }

        async function loadGallery() {
            renderGallery(fallbackGallery);
            try {
                const response = await fetch('php/api.php?action=gallery');
                const result = await response.json();
                if (response.ok && result?.items?.length) {
                    renderGallery(result.items);
                }
            } catch (error) {
                // Keep the fallback gallery.
            }
        }

        function renderMatches(items) {
            if (!matchesList) return;
            if (!items || !items.length) {
                matchesList.innerHTML = '<div class="rounded-2xl border border-gray-800 bg-clubDark p-6 text-center text-gray-400">No fixtures are available yet.</div>';
                return;
            }

            matchesList.innerHTML = items.map((item) => {
                const isUpcoming = item.status === 'upcoming';
                const badge = isUpcoming
                    ? '<span class="rounded-full border border-clubYellow/20 bg-clubYellow/10 px-3 py-1 text-[11px] font-semibold uppercase tracking-[0.2em] text-clubYellow">Upcoming</span>'
                    : '<span class="rounded-full border border-green-500/20 bg-green-500/10 px-3 py-1 text-[11px] font-semibold uppercase tracking-[0.2em] text-green-400">Result</span>';
                const score = item.resultHome !== null && item.resultAway !== null
                    ? `<span class="text-clubYellow font-black">${item.resultHome} - ${item.resultAway}</span>`
                    : '<span class="text-gray-400">Fixture</span>';

                return `
                    <div class="rounded-2xl border border-gray-800 bg-clubDark p-6 shadow-lg">
                        <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
                            <div>
                                <div class="mb-2 flex flex-wrap items-center gap-2">${badge}<span class="text-[11px] uppercase tracking-[0.25em] text-gray-500">${escapeHtml(item.competition || 'Match')}</span></div>
                                <h4 class="text-lg font-bold text-white">${escapeHtml(item.homeTeam)} <span class="text-clubYellow">vs</span> ${escapeHtml(item.awayTeam)}</h4>
                                <p class="mt-2 text-sm text-gray-400"><i class="far fa-calendar text-clubYellow mr-2"></i>${escapeHtml(formatDate(item.matchDate))}${item.venue ? ` · ${escapeHtml(item.venue)}` : ''}</p>
                            </div>
                            <div class="rounded-xl border border-gray-800 bg-clubDarker px-4 py-3 text-center text-sm font-semibold text-gray-300">
                                ${score}
                            </div>
                        </div>
                    </div>
                `;
            }).join('');
        }

        async function loadMatches() {
            renderMatches(fallbackMatches);
            try {
                const response = await fetch('php/api.php?action=matches');
                const result = await response.json();
                if (response.ok && result?.items?.length) {
                    renderMatches(result.items);
                }
            } catch (error) {
                // Keep the fallback matches.
            }
        }

        function renderStandings(items) {
            if (!standingsTable) return;
            if (!items || !items.length) {
                standingsTable.innerHTML = '<div class="rounded-2xl border border-gray-800 bg-clubDark p-6 text-center text-gray-400">No standings available yet.</div>';
                return;
            }

            standingsTable.innerHTML = `
                <div class="overflow-hidden rounded-2xl border border-gray-800 bg-clubDark">
                    <table class="w-full text-left text-sm">
                        <thead class="bg-gray-900 text-gray-400 uppercase text-xs">
                            <tr>
                                <th class="py-3 px-4">Pos / Team</th>
                                <th class="py-3 px-2 text-center">PL</th>
                                <th class="py-3 px-2 text-center">PTS</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-800">
                            ${items.map((item) => `
                                <tr class="${item.position === 1 ? 'bg-clubYellow/10 font-bold text-white' : 'text-gray-300'}">
                                    <td class="py-3 px-4 flex items-center space-x-2">
                                        <span class="${item.position === 1 ? 'text-clubYellow' : 'text-gray-400'}">${escapeHtml(item.position)}</span>
                                        <span>${escapeHtml(item.teamName)}</span>
                                    </td>
                                    <td class="py-3 px-2 text-center">${escapeHtml(item.played)}</td>
                                    <td class="py-3 px-2 text-center ${item.position === 1 ? 'text-clubYellow' : ''}">${escapeHtml(item.points)}</td>
                                </tr>
                            `).join('')}
                        </tbody>
                    </table>
                </div>
            `;
        }

        async function loadStandings() {
            renderStandings(fallbackStandings);
            try {
                const response = await fetch('php/api.php?action=standings');
                const result = await response.json();
                if (response.ok && result?.items?.length) {
                    renderStandings(result.items);
                }
            } catch (error) {
                // Keep the fallback standings.
            }
        }

        const themeToggle = document.getElementById('theme-toggle');
        const themeToggleIcon = document.getElementById('theme-toggle-icon');
        const themeToggleLabel = document.getElementById('theme-toggle-label');

        function applyTheme(theme) {
            const isLight = theme === 'light';
            document.body.classList.toggle('theme-light', isLight);
            localStorage.setItem('tonj-theme', theme);

            if (themeToggle) {
                themeToggle.setAttribute('aria-pressed', String(isLight));
                themeToggle.setAttribute('aria-label', isLight ? 'Switch to dark mode' : 'Switch to light mode');
                if (themeToggleIcon) {
                    themeToggleIcon.className = isLight ? 'fas fa-moon' : 'fas fa-sun';
                }
                if (themeToggleLabel) {
                    themeToggleLabel.textContent = isLight ? 'Dark' : 'Light';
                }
            }
        }

        function initTheme() {
            const storedTheme = localStorage.getItem('tonj-theme');
            const prefersLight = window.matchMedia('(prefers-color-scheme: light)').matches;
            applyTheme(storedTheme || (prefersLight ? 'light' : 'dark'));
        }

        if (themeToggle) {
            themeToggle.addEventListener('click', () => {
                const nextTheme = document.body.classList.contains('theme-light') ? 'dark' : 'light';
                applyTheme(nextTheme);
            });
        }

        initTheme();

        // Mobile Menu Toggle
        const menuBtn = document.getElementById('menu-btn');
        const mobileMenu = document.getElementById('mobile-menu');

        if (menuBtn && mobileMenu) {
            menuBtn.addEventListener('click', () => {
                const isHidden = mobileMenu.classList.toggle('hidden');
                menuBtn.setAttribute('aria-expanded', String(!isHidden));
            });

            mobileMenu.querySelectorAll('a').forEach((link) => {
                link.addEventListener('click', () => {
                    mobileMenu.classList.add('hidden');
                    menuBtn.setAttribute('aria-expanded', 'false');
                });
            });
        }

        const modalClose = document.getElementById('news-modal-close');
        const modal = document.getElementById('news-modal');

        if (modalClose) {
            modalClose.addEventListener('click', closeNewsModal);
        }

        if (modal) {
            modal.addEventListener('click', (event) => {
                if (event.target === modal) {
                    closeNewsModal();
                }
            });
        }

        document.addEventListener('keydown', (event) => {
            if (event.key === 'Escape') {
                closeNewsModal();
            }
        });

        Promise.all([loadNews(), loadGallery(), loadMatches(), loadStandings()]);

        // Squad Filtering Logic
        function filterSquad(position, button = null) {
            const cards = document.querySelectorAll('.player-card');
            const tabs = document.querySelectorAll('.squad-tab');

            tabs.forEach((tab) => {
                tab.classList.remove('bg-clubYellow', 'text-black');
                tab.classList.add('bg-gray-800', 'text-gray-300');
            });

            if (button) {
                button.classList.remove('bg-gray-800', 'text-gray-300');
                button.classList.add('bg-clubYellow', 'text-black');
            }

            cards.forEach((card) => {
                if (position === 'all' || card.getAttribute('data-position') === position) {
                    card.style.display = 'block';
                } else {
                    card.style.display = 'none';
                }
            });
        }

        // Contact Form Handler
        const contactForm = document.getElementById('contact-form');
        const successBox = document.getElementById('form-success');
        const submitBtn = document.getElementById('submit-btn');
        const nameInput = document.getElementById('contact-name');
        const emailInput = document.getElementById('contact-email');
        const messageInput = document.getElementById('contact-message');
        const nameError = document.getElementById('name-error');
        const emailError = document.getElementById('email-error');
        const messageError = document.getElementById('message-error');

        function updateFeedback(message, type = 'success') {
            successBox.className = `mt-4 p-4 rounded-xl text-center text-sm font-bold ${type === 'success' ? 'bg-green-500/10 border border-green-500/30 text-green-400' : 'bg-red-500/10 border border-red-500/30 text-red-400'}`;
            successBox.textContent = message;
            successBox.classList.remove('hidden');
        }

        function validateField(input, errorEl, validator) {
            const isValid = validator(input.value.trim());
            input.classList.toggle('border-red-500', !isValid);
            input.classList.toggle('border-gray-800', isValid);
            errorEl.classList.toggle('hidden', isValid);
            return isValid;
        }

        function validateForm() {
            const isNameValid = validateField(nameInput, nameError, (value) => value.length >= 2);
            const isEmailValid = validateField(emailInput, emailError, (value) => /.+@.+\..+/.test(value) || /^\+?[0-9\s-]{7,15}$/.test(value));
            const isMessageValid = validateField(messageInput, messageError, (value) => value.length >= 10);
            submitBtn.disabled = !(isNameValid && isEmailValid && isMessageValid);
            return isNameValid && isEmailValid && isMessageValid;
        }

        [nameInput, emailInput, messageInput].forEach((input) => {
            input.addEventListener('input', validateForm);
        });

        if (contactForm) {
            contactForm.addEventListener('submit', async function (e) {
                e.preventDefault();
                if (!validateForm()) return;

                submitBtn.disabled = true;
                submitBtn.textContent = 'Sending...';
                successBox.className = 'hidden mt-4 p-4 rounded-xl text-center text-sm font-bold';

                const formData = new FormData(contactForm);

                try {
                    const response = await fetch('php/contact.php', {
                        method: 'POST',
                        body: formData
                    });

                    const responseText = await response.text();
                    let result = {};
                    try {
                        result = JSON.parse(responseText);
                    } catch (error) {
                        result = { success: false, message: responseText || 'Unable to send message.' };
                    }

                    if (result.success && result.mailtoUrl) {
                        window.open(result.mailtoUrl, '_blank', 'noopener,noreferrer');
                        updateFeedback(result.message || 'Opening your email app with your message ready to send.', 'success');
                        contactForm.reset();
                        validateForm();
                    } else {
                        updateFeedback(result.message || 'Unable to send message right now.', 'error');
                    }
                } catch (error) {
                    updateFeedback('Unable to reach the server right now. Please try again later.', 'error');
                } finally {
                    submitBtn.disabled = false;
                    submitBtn.textContent = 'Send Message';
                }
            });
        }
