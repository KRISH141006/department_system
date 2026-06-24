(function () {
    "use strict";

    const config = window.AppUX || {};
    const services = Array.isArray(config.services) ? config.services : [];
    const recentKey = `ict_recent_services_${config.role || "user"}`;
    let palette = null;
    let searchInput = null;
    let resultsList = null;
    let activeIndex = 0;
    let currentResults = [];

    document.addEventListener("DOMContentLoaded", () => {
        buildCommandPalette();
        bindCommandTriggers();
        bindSidebarAccordions();
        enhancePageChrome();
        enhanceAlerts();
        enhanceForms();
        enhanceTables();
        markCurrentService();
        enhanceClickableSurfaces();
    });

    function buildCommandPalette() {
        if (!services.length) return;

        palette = document.createElement("div");
        palette.className = "ux-command-palette";
        palette.hidden = true;
        palette.innerHTML = `
            <div class="ux-command-backdrop" data-close-command-palette></div>
            <section class="ux-command-panel" role="dialog" aria-modal="true" aria-label="Service search">
                <div class="ux-command-search">
                    <span aria-hidden="true">/</span>
                    <input type="text" placeholder="Search any service, action, module..." aria-label="Search services">
                    <kbd>Esc</kbd>
                </div>
                <div class="ux-command-meta">
                    <span>Type a service name or module</span>
                    <span>Enter to open</span>
                </div>
                <div class="ux-command-results" role="listbox"></div>
            </section>
        `;
        document.body.appendChild(palette);

        searchInput = palette.querySelector("input");
        resultsList = palette.querySelector(".ux-command-results");

        searchInput.addEventListener("input", () => renderResults(searchInput.value));
        searchInput.addEventListener("keydown", handleSearchKeydown);
        palette.addEventListener("click", (event) => {
            if (event.target.closest("[data-close-command-palette]")) {
                closePalette();
            }
            const item = event.target.closest("[data-service-index]");
            if (item) {
                openService(currentResults[Number(item.dataset.serviceIndex)]);
            }
        });

        document.addEventListener("keydown", (event) => {
            const isCommand = (event.ctrlKey || event.metaKey) && event.key.toLowerCase() === "k";
            if (isCommand) {
                event.preventDefault();
                openPalette();
            } else if (event.key === "Escape" && palette && !palette.hidden) {
                closePalette();
            }
        });
    }

    function bindCommandTriggers() {
        document.querySelectorAll("[data-open-command-palette]").forEach((trigger) => {
            trigger.addEventListener("click", openPalette);
        });
    }

    function bindSidebarAccordions() {
        const groups = Array.from(document.querySelectorAll("[data-sidebar-group]"));
        if (!groups.length) return;

        groups.forEach((group) => {
            const toggle = group.querySelector("[data-sidebar-group-toggle]");
            const hasActiveLink = !!group.querySelector(".sidebar-link.active");
            setSidebarGroup(group, hasActiveLink || group.classList.contains("is-open"), false);

            if (!toggle) return;
            toggle.addEventListener("click", () => {
                const shouldOpen = !group.classList.contains("is-open");

                if (shouldOpen) {
                    groups.forEach((otherGroup) => {
                        if (otherGroup !== group) {
                            setSidebarGroup(otherGroup, false, true);
                        }
                    });
                }

                setSidebarGroup(group, shouldOpen, true);
            });
        });
    }

    function setSidebarGroup(group, shouldOpen, animate) {
        const toggle = group.querySelector("[data-sidebar-group-toggle]");
        const panel = group.querySelector(".ux-service-group-panel");
        if (!panel) return;

        window.clearTimeout(Number(panel.dataset.closeTimer || 0));
        group.classList.toggle("is-open", shouldOpen);
        toggle?.setAttribute("aria-expanded", shouldOpen ? "true" : "false");

        if (!animate) {
            panel.hidden = !shouldOpen;
            panel.style.maxHeight = shouldOpen ? "none" : "0px";
            panel.style.opacity = shouldOpen ? "1" : "0";
            return;
        }

        if (shouldOpen) {
            panel.hidden = false;
            panel.style.maxHeight = "0px";
            panel.style.opacity = "0";

            requestAnimationFrame(() => {
                panel.style.maxHeight = `${panel.scrollHeight}px`;
                panel.style.opacity = "1";
            });

            const clearOpenHeight = (event) => {
                if (event.propertyName !== "max-height") return;
                panel.removeEventListener("transitionend", clearOpenHeight);
                if (group.classList.contains("is-open")) {
                    panel.style.maxHeight = "none";
                }
            };
            panel.addEventListener("transitionend", clearOpenHeight);
            return;
        }

        if (panel.hidden) return;
        panel.style.maxHeight = `${panel.scrollHeight}px`;
        panel.style.opacity = "1";

        requestAnimationFrame(() => {
            panel.style.maxHeight = "0px";
            panel.style.opacity = "0";
        });

        panel.dataset.closeTimer = String(window.setTimeout(() => {
            if (!group.classList.contains("is-open")) {
                panel.hidden = true;
            }
        }, 280));
    }

    function openPalette() {
        if (!palette) return;
        palette.hidden = false;
        document.body.classList.add("ux-command-open");
        searchInput.value = "";
        activeIndex = 0;
        renderResults("");
        requestAnimationFrame(() => searchInput.focus());
    }

    function closePalette() {
        if (!palette) return;
        palette.hidden = true;
        document.body.classList.remove("ux-command-open");
    }

    function handleSearchKeydown(event) {
        if (!currentResults.length) return;

        if (event.key === "ArrowDown") {
            event.preventDefault();
            activeIndex = (activeIndex + 1) % currentResults.length;
            updateActiveResult();
        } else if (event.key === "ArrowUp") {
            event.preventDefault();
            activeIndex = (activeIndex - 1 + currentResults.length) % currentResults.length;
            updateActiveResult();
        } else if (event.key === "Enter") {
            event.preventDefault();
            openService(currentResults[activeIndex]);
        }
    }

    function renderResults(query) {
        const term = normalize(query);
        const recentHrefs = getRecentServices();
        const scored = services
            .map((service) => ({
                ...service,
                score: scoreService(service, term, recentHrefs)
            }))
            .filter((service) => term === "" || service.score > 0)
            .sort((a, b) => b.score - a.score || a.label.localeCompare(b.label));

        currentResults = scored.slice(0, 12);
        activeIndex = Math.min(activeIndex, Math.max(currentResults.length - 1, 0));

        if (!currentResults.length) {
            resultsList.innerHTML = '<div class="ux-command-empty">No matching service found.</div>';
            return;
        }

        resultsList.innerHTML = currentResults.map((service, index) => {
            const recent = recentHrefs.includes(service.href) ? '<span class="ux-command-chip">Recent</span>' : '';
            return `
                <button type="button" class="ux-command-item ${index === activeIndex ? 'is-active' : ''}" data-service-index="${index}" role="option">
                    <span class="ux-command-mark">${escapeHtml(service.mark || service.label.slice(0, 1))}</span>
                    <span class="ux-command-copy">
                        <strong>${escapeHtml(service.label)}</strong>
                        <small>${escapeHtml(service.group || "Service")}</small>
                    </span>
                    ${recent}
                </button>
            `;
        }).join("");
    }

    function updateActiveResult() {
        resultsList.querySelectorAll(".ux-command-item").forEach((item, index) => {
            item.classList.toggle("is-active", index === activeIndex);
            if (index === activeIndex) {
                item.scrollIntoView({ block: "nearest" });
            }
        });
    }

    function scoreService(service, term, recentHrefs) {
        const haystack = normalize(`${service.label} ${service.group} ${service.keywords || ""}`);
        let score = recentHrefs.includes(service.href) ? 6 : 0;
        if (!term) return score + 1;
        if (normalize(service.label).startsWith(term)) score += 40;
        if (haystack.includes(term)) score += 20;
        term.split(/\s+/).forEach((part) => {
            if (part && haystack.includes(part)) score += 8;
        });
        return score;
    }

    function openService(service) {
        if (!service || !service.href) return;
        rememberService(service.href);
        window.location.href = service.href;
    }

    function getRecentServices() {
        try {
            return JSON.parse(localStorage.getItem(recentKey) || "[]");
        } catch (error) {
            return [];
        }
    }

    function rememberService(href) {
        const next = [href, ...getRecentServices().filter((item) => item !== href)].slice(0, 6);
        localStorage.setItem(recentKey, JSON.stringify(next));
    }

    function markCurrentService() {
        const current = window.location.pathname.replace(/\/$/, "");
        const match = services.find((service) => service.href.replace(/\/$/, "") === current);
        if (match) rememberService(match.href);
    }

    function enhanceAlerts() {
        const alerts = document.querySelectorAll(".alert");
        if (!alerts.length) return;

        const stack = document.createElement("div");
        stack.className = "ux-toast-stack";
        document.body.appendChild(stack);

        alerts.forEach((alert, index) => {
            const toast = document.createElement("div");
            toast.className = `ux-toast ${alert.classList.contains("alert-error") ? "is-error" : "is-success"}`;
            toast.innerHTML = `
                <span>${escapeHtml(alert.textContent.trim())}</span>
                <button type="button" aria-label="Dismiss message">x</button>
            `;
            toast.querySelector("button").addEventListener("click", () => toast.remove());
            stack.appendChild(toast);
            setTimeout(() => toast.classList.add("is-visible"), 80 + index * 80);
            setTimeout(() => toast.remove(), 6200 + index * 400);
        });
    }

    function enhanceForms() {
        document.querySelectorAll("form").forEach((form) => {
            form.classList.add("ux-enhanced-form");
            form.querySelectorAll("input, select, textarea").forEach((field) => {
                if (field.required) {
                    field.closest(".form-group")?.classList.add("is-required");
                }
            });

            const submitter = form.querySelector('button[type="submit"], input[type="submit"]');
            const actionRow = submitter?.closest(".form-actions, .card-actions, .section-actions") || submitter?.parentElement;
            if (actionRow && actionRow !== form) {
                actionRow.classList.add("ux-submit-row");
            }

            form.addEventListener("submit", () => {
                if (!submitter || submitter.dataset.noLoading === "1") return;
                if (submitter.tagName === "BUTTON") {
                    submitter.dataset.originalText = submitter.innerHTML;
                    submitter.innerHTML = '<span class="ux-spinner"></span><span>Working...</span>';
                }
                submitter.classList.add("is-loading");
                submitter.setAttribute("aria-busy", "true");
            });
        });
    }

    function enhanceTables() {
        document.querySelectorAll(".table-container table, table").forEach((table) => {
            if (table.closest(".ux-no-table-enhance")) return;
            table.classList.add("ux-data-table");
            addMobileLabels(table);
            addTableFilter(table);
        });
    }

    function enhancePageChrome() {
        document.body.classList.add("ux-system-ready");

        const header = document.querySelector(".wrapper > .section-header:first-child, .page-wrap > .section-header:first-child, .dashboard-header:first-child");
        if (header) {
            header.classList.add("ux-page-hero");
        }

        document.querySelectorAll(".wrapper, .page-wrap").forEach((wrap) => {
            if (!wrap.closest(".ux-command-palette")) {
                wrap.classList.add("ux-page-shell");
            }
        });
    }

    function addMobileLabels(table) {
        const headers = Array.from(table.querySelectorAll("thead th")).map((th) => th.textContent.trim());
        if (!headers.length) return;

        table.querySelectorAll("tbody tr").forEach((row) => {
            Array.from(row.children).forEach((cell, index) => {
                if (headers[index] && !cell.dataset.label) {
                    cell.dataset.label = headers[index];
                }
            });
        });
    }

    function addTableFilter(table) {
        const rows = Array.from(table.querySelectorAll("tbody tr"));
        if (rows.length < 8 || table.dataset.uxFiltered === "1") return;
        table.dataset.uxFiltered = "1";

        const container = table.closest(".table-container") || table.parentElement;
        if (!container) return;

        const filter = document.createElement("div");
        filter.className = "ux-table-toolbar";
        filter.innerHTML = `
            <label>
                <span>Search</span>
                <input type="search" placeholder="Search rows">
            </label>
            <strong>${rows.length} rows</strong>
        `;
        container.insertBefore(filter, table);

        const input = filter.querySelector("input");
        const count = filter.querySelector("strong");
        input.addEventListener("input", () => {
            const term = normalize(input.value);
            let visible = 0;

            rows.forEach((row) => {
                const isMatch = !term || normalize(row.textContent).includes(term);
                row.hidden = !isMatch;
                if (isMatch) visible++;
            });

            count.textContent = `${visible} rows`;
        });
    }

    function enhanceClickableSurfaces() {
        document.querySelectorAll("a.card, .ux-action-row, .task-strip, .faculty-card").forEach((item) => {
            item.classList.add("ux-clickable-surface");
        });
    }

    function normalize(value) {
        return String(value || "").toLowerCase().replace(/[^a-z0-9]+/g, " ").trim();
    }

    function escapeHtml(value) {
        const div = document.createElement("div");
        div.textContent = value == null ? "" : String(value);
        return div.innerHTML;
    }

    function escapeAttribute(value) {
        return escapeHtml(value).replace(/"/g, "&quot;");
    }
})();
