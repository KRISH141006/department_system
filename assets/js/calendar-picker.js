(function () {
    "use strict";

    const MONTHS = [
        "January", "February", "March", "April", "May", "June",
        "July", "August", "September", "October", "November", "December"
    ];
    const WEEKDAYS = ["Sun", "Mon", "Tue", "Wed", "Thu", "Fri", "Sat"];
    const ICON = '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><rect x="3" y="4" width="18" height="18" rx="2"/><path d="M16 2v4M8 2v4M3 10h18"/></svg>';
    const CLOCK = '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><circle cx="12" cy="12" r="9"/><path d="M12 7v5l3 2"/></svg>';

    let active = null;
    let picker = null;
    let isCommitting = false;

    document.addEventListener("DOMContentLoaded", initDateTimePicker);

    function initDateTimePicker() {
        picker = buildPicker();
        document.body.appendChild(picker.root);

        document.querySelectorAll("input").forEach((input) => {
            const mode = detectMode(input);
            if (!mode) return;
            enhanceInput(input, mode);
        });

        document.addEventListener("mousedown", handleOutsideClick);
        document.addEventListener("keydown", (event) => {
            if (event.key === "Escape") closePicker();
        });
        window.addEventListener("resize", closePicker);
        window.addEventListener("scroll", positionPicker, true);
    }

    function detectMode(input) {
        if (!input || input.dataset.dtpEnhanced === "1" || input.dataset.calendarPicker === "off") {
            return null;
        }
        if (input.closest(".dtp-popover")) return null;
        if (input.type === "hidden" || input.type === "file" || input.disabled) return null;

        const type = (input.getAttribute("type") || "text").toLowerCase();
        const key = `${input.name || ""} ${input.id || ""} ${input.className || ""}`.toLowerCase();

        if (type === "date") return "date";
        if (type === "time") return "time";
        if (type === "datetime-local") return "datetime";
        if (type === "text" && /(deadline|due_date|due-at|due_at|datetime|date_time)/.test(key)) {
            return "datetime";
        }
        if (input.dataset.calendarPicker === "date") return "date";
        if (input.dataset.calendarPicker === "time") return "time";
        if (input.dataset.calendarPicker === "datetime") return "datetime";
        return null;
    }

    function enhanceInput(input, mode) {
        const originalType = (input.getAttribute("type") || "text").toLowerCase();
        input.dataset.dtpEnhanced = "1";
        input.dataset.dtpMode = mode;
        input.dataset.dtpOriginalType = originalType;
        input.autocomplete = "off";
        input.classList.add("dtp-input");

        if (originalType !== "text") {
            try {
                input.type = "text";
            } catch (error) {
                input.setAttribute("type", "text");
            }
        }

        if (!input.placeholder) {
            input.placeholder = mode === "time" ? "Select time" : (mode === "date" ? "Select date" : "Select date and time");
        }

        const wrapper = document.createElement("div");
        wrapper.className = "dtp-field";
        input.parentNode.insertBefore(wrapper, input);
        wrapper.appendChild(input);

        const trigger = document.createElement("button");
        trigger.type = "button";
        trigger.className = "dtp-trigger";
        trigger.setAttribute("aria-label", mode === "time" ? "Open time picker" : "Open calendar picker");
        trigger.innerHTML = mode === "time" ? CLOCK : ICON;
        wrapper.appendChild(trigger);

        input.addEventListener("focus", () => openPicker(input));
        input.addEventListener("click", () => openPicker(input));
        input.addEventListener("input", () => syncTypedValue(input));
        input.addEventListener("keydown", (event) => {
            if (event.key === "ArrowDown") {
                event.preventDefault();
                openPicker(input);
            }
        });
        trigger.addEventListener("click", () => openPicker(input));
    }

    function buildPicker() {
        const root = document.createElement("div");
        root.className = "dtp-popover";
        root.hidden = true;
        root.innerHTML = `
            <div class="dtp-date-panel">
                <div class="dtp-header">
                    <button type="button" class="dtp-nav" data-action="prev-month" aria-label="Previous month">&lt;</button>
                    <div class="dtp-month-label"></div>
                    <button type="button" class="dtp-nav" data-action="next-month" aria-label="Next month">&gt;</button>
                </div>
                <div class="dtp-weekdays"></div>
                <div class="dtp-days"></div>
            </div>
            <div class="dtp-time-panel">
                <div class="dtp-time-title">Time</div>
                <div class="dtp-time-controls">
                    <div class="dtp-time-unit">
                        <button type="button" class="dtp-step" data-time-step="hour:-1" aria-label="Decrease hour">-</button>
                        <input type="text" class="dtp-time-value" data-time-part="hour" maxlength="2" inputmode="numeric" data-dtp-internal="1">
                        <button type="button" class="dtp-step" data-time-step="hour:1" aria-label="Increase hour">+</button>
                    </div>
                    <span class="dtp-time-separator">:</span>
                    <div class="dtp-time-unit">
                        <button type="button" class="dtp-step" data-time-step="minute:-5" aria-label="Decrease minute">-</button>
                        <input type="text" class="dtp-time-value" data-time-part="minute" maxlength="2" inputmode="numeric" data-dtp-internal="1">
                        <button type="button" class="dtp-step" data-time-step="minute:5" aria-label="Increase minute">+</button>
                    </div>
                </div>
            </div>
            <div class="dtp-actions">
                <button type="button" class="dtp-action ghost" data-action="clear">Clear</button>
                <div style="display:flex; gap:0.5rem;">
                    <button type="button" class="dtp-action" data-action="today">Today</button>
                    <button type="button" class="dtp-action primary" data-action="done">Done</button>
                </div>
            </div>
        `;

        const weekdays = root.querySelector(".dtp-weekdays");
        WEEKDAYS.forEach((day) => {
            const item = document.createElement("div");
            item.className = "dtp-weekday";
            item.textContent = day;
            weekdays.appendChild(item);
        });

        root.addEventListener("mousedown", (event) => event.stopPropagation());
        root.addEventListener("click", handlePickerClick);
        root.addEventListener("input", handleTimeInput);
        root.addEventListener("focusout", handleTimeBlur);

        return {
            root,
            datePanel: root.querySelector(".dtp-date-panel"),
            timePanel: root.querySelector(".dtp-time-panel"),
            label: root.querySelector(".dtp-month-label"),
            days: root.querySelector(".dtp-days"),
            hour: root.querySelector('[data-time-part="hour"]'),
            minute: root.querySelector('[data-time-part="minute"]'),
            clear: root.querySelector('[data-action="clear"]'),
            today: root.querySelector('[data-action="today"]')
        };
    }

    function openPicker(input) {
        const mode = input.dataset.dtpMode;
        const originalType = input.dataset.dtpOriginalType || "text";
        const min = parseLimit(input.getAttribute("min"), mode, originalType);
        const max = parseLimit(input.getAttribute("max"), mode, originalType);
        let selected = parseValue(input.value, mode, originalType);

        if (!selected) {
            selected = new Date();
            selected.setSeconds(0, 0);
        }
        if (min && selected < min) selected = new Date(min);
        if (max && selected > max) selected = new Date(max);

        active = {
            input,
            mode,
            originalType,
            selected,
            view: new Date(selected.getFullYear(), selected.getMonth(), 1),
            min,
            max
        };

        picker.datePanel.classList.toggle("is-hidden", mode === "time");
        picker.timePanel.classList.toggle("is-hidden", mode === "date");
        picker.clear.hidden = input.required;
        picker.today.hidden = mode === "time";

        document.querySelectorAll(".dtp-field.is-open").forEach((node) => node.classList.remove("is-open"));
        input.closest(".dtp-field")?.classList.add("is-open");

        renderPicker();
        picker.root.hidden = false;
        picker.root.style.visibility = "hidden";
        positionPicker();
        picker.root.style.visibility = "visible";
    }

    function closePicker() {
        if (!picker || picker.root.hidden) return;
        picker.root.hidden = true;
        picker.root.style.visibility = "";
        document.querySelectorAll(".dtp-field.is-open").forEach((node) => node.classList.remove("is-open"));
        active = null;
    }

    function handleOutsideClick(event) {
        if (!active) return;
        if (picker.root.contains(event.target)) return;
        if (active.input.closest(".dtp-field")?.contains(event.target)) return;
        closePicker();
    }

    function positionPicker() {
        if (!active || !picker || picker.root.hidden) return;
        const rect = active.input.getBoundingClientRect();
        const margin = 12;
        const width = picker.root.offsetWidth || 360;
        const height = picker.root.offsetHeight || 420;
        let left = rect.left + window.scrollX;
        let top = rect.bottom + window.scrollY + 8;

        left = Math.min(left, window.scrollX + window.innerWidth - width - margin);
        left = Math.max(window.scrollX + margin, left);

        if (top + height > window.scrollY + window.innerHeight - margin) {
            top = rect.top + window.scrollY - height - 8;
        }
        if (top < window.scrollY + margin) {
            top = window.scrollY + margin;
        }

        picker.root.style.left = `${left}px`;
        picker.root.style.top = `${top}px`;
    }

    function renderPicker() {
        if (!active) return;
        if (active.mode !== "time") renderCalendar();
        if (active.mode !== "date") renderTime();
    }

    function renderCalendar() {
        picker.label.textContent = `${MONTHS[active.view.getMonth()]} ${active.view.getFullYear()}`;
        picker.days.innerHTML = "";

        const year = active.view.getFullYear();
        const month = active.view.getMonth();
        const firstDay = new Date(year, month, 1);
        const start = new Date(year, month, 1 - firstDay.getDay());
        const todayKey = dateKey(new Date());
        const selectedKey = dateKey(active.selected);

        for (let i = 0; i < 42; i += 1) {
            const current = new Date(start);
            current.setDate(start.getDate() + i);
            const button = document.createElement("button");
            button.type = "button";
            button.className = "dtp-day";
            button.textContent = current.getDate();
            button.dataset.date = formatDate(current);

            if (current.getMonth() !== month) button.classList.add("is-muted");
            if (dateKey(current) === todayKey) button.classList.add("is-today");
            if (dateKey(current) === selectedKey) button.classList.add("is-selected");
            if (isDateDisabled(current)) {
                button.classList.add("is-disabled");
                button.disabled = true;
            }

            picker.days.appendChild(button);
        }
    }

    function renderTime() {
        picker.hour.value = pad(active.selected.getHours());
        picker.minute.value = pad(active.selected.getMinutes());
    }

    function handlePickerClick(event) {
        const day = event.target.closest(".dtp-day");
        if (day && !day.disabled) {
            const selectedDate = parseDateOnly(day.dataset.date);
            active.selected.setFullYear(selectedDate.getFullYear(), selectedDate.getMonth(), selectedDate.getDate());
            clampSelected();
            commitValue();
            if (active.mode === "date") {
                closePicker();
            } else {
                active.view = new Date(active.selected.getFullYear(), active.selected.getMonth(), 1);
                renderPicker();
            }
            return;
        }

        const step = event.target.closest("[data-time-step]");
        if (step) {
            const [part, delta] = step.dataset.timeStep.split(":");
            adjustTime(part, Number(delta));
            return;
        }

        const action = event.target.closest("[data-action]")?.dataset.action;
        if (!action || !active) return;

        if (action === "prev-month") {
            active.view.setMonth(active.view.getMonth() - 1);
            renderCalendar();
        } else if (action === "next-month") {
            active.view.setMonth(active.view.getMonth() + 1);
            renderCalendar();
        } else if (action === "today") {
            const now = new Date();
            active.selected.setFullYear(now.getFullYear(), now.getMonth(), now.getDate());
            if (active.mode !== "date") {
                active.selected.setHours(23, 59, 0, 0);
            }
            active.view = new Date(now.getFullYear(), now.getMonth(), 1);
            clampSelected();
            commitValue();
            renderPicker();
        } else if (action === "clear") {
            active.input.value = "";
            dispatchChange(active.input);
            closePicker();
        } else if (action === "done") {
            clampSelected();
            commitValue();
            closePicker();
        }
    }

    function handleTimeInput(event) {
        if (!active) return;
        const target = event.target;
        if (!target.matches(".dtp-time-value")) return;
        const part = target.dataset.timePart;
        const max = part === "hour" ? 23 : 59;
        const cleanValue = target.value.replace(/\D/g, "").slice(0, 2);
        target.value = cleanValue;
        if (cleanValue === "") return;
        const value = clamp(Number(cleanValue), 0, max);

        if (part === "hour") {
            active.selected.setHours(value);
        } else {
            active.selected.setMinutes(value);
        }
        active.selected.setSeconds(0, 0);
        clampSelected();
        commitValue();
    }

    function handleTimeBlur(event) {
        if (!active || !event.target.matches(".dtp-time-value")) return;
        renderTime();
    }

    function adjustTime(part, delta) {
        if (part === "hour") {
            active.selected.setHours(active.selected.getHours() + delta);
        } else {
            active.selected.setMinutes(active.selected.getMinutes() + delta);
        }
        active.selected.setSeconds(0, 0);
        clampSelected();
        commitValue();
        renderTime();
    }

    function commitValue() {
        if (!active) return;
        active.input.value = formatValue(active.selected, active.mode, active.originalType);
        isCommitting = true;
        try {
            dispatchChange(active.input);
        } finally {
            isCommitting = false;
        }
    }

    function syncTypedValue(input) {
        if (isCommitting) return;
        const parsed = parseValue(input.value, input.dataset.dtpMode, input.dataset.dtpOriginalType || "text");
        if (!parsed || active?.input !== input) return;
        active.selected = parsed;
        active.view = new Date(parsed.getFullYear(), parsed.getMonth(), 1);
        clampSelected();
        renderPicker();
    }

    function dispatchChange(input) {
        input.dispatchEvent(new Event("input", { bubbles: true }));
        input.dispatchEvent(new Event("change", { bubbles: true }));
    }

    function isDateDisabled(date) {
        if (active.min && dateKey(date) < dateKey(active.min)) return true;
        if (active.max && dateKey(date) > dateKey(active.max)) return true;
        return false;
    }

    function clampSelected() {
        if (active.min && active.selected < active.min) active.selected = new Date(active.min);
        if (active.max && active.selected > active.max) active.selected = new Date(active.max);
    }

    function parseValue(value, mode, originalType) {
        const clean = (value || "").trim();
        if (!clean) return null;
        if (mode === "time") return parseTimeOnly(clean);
        if (mode === "date") return parseDateOnly(clean);
        return parseDateTime(clean, originalType);
    }

    function parseLimit(value, mode, originalType) {
        if (!value) return null;
        return parseValue(value, mode, originalType);
    }

    function parseDateTime(value) {
        const match = value.match(/^(\d{4})-(\d{2})-(\d{2})[T ](\d{1,2}):(\d{2})/);
        if (match) {
            return new Date(Number(match[1]), Number(match[2]) - 1, Number(match[3]), Number(match[4]), Number(match[5]), 0, 0);
        }
        const parsed = new Date(value.replace(" ", "T"));
        return Number.isNaN(parsed.getTime()) ? null : parsed;
    }

    function parseDateOnly(value) {
        const match = (value || "").match(/^(\d{4})-(\d{2})-(\d{2})/);
        if (!match) return null;
        return new Date(Number(match[1]), Number(match[2]) - 1, Number(match[3]), 12, 0, 0, 0);
    }

    function parseTimeOnly(value) {
        const match = (value || "").match(/^(\d{1,2}):(\d{2})/);
        if (!match) return null;
        const date = new Date();
        date.setHours(clamp(Number(match[1]), 0, 23), clamp(Number(match[2]), 0, 59), 0, 0);
        return date;
    }

    function formatValue(date, mode, originalType) {
        if (mode === "time") return `${pad(date.getHours())}:${pad(date.getMinutes())}`;
        if (mode === "date") return formatDate(date);
        const separator = originalType === "datetime-local" ? "T" : " ";
        return `${formatDate(date)}${separator}${pad(date.getHours())}:${pad(date.getMinutes())}`;
    }

    function formatDate(date) {
        return `${date.getFullYear()}-${pad(date.getMonth() + 1)}-${pad(date.getDate())}`;
    }

    function dateKey(date) {
        return Number(formatDate(date).replace(/-/g, ""));
    }

    function pad(value) {
        return String(value).padStart(2, "0");
    }

    function clamp(value, min, max) {
        if (Number.isNaN(value)) return min;
        return Math.min(Math.max(value, min), max);
    }
})();
