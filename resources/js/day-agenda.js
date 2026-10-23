/*
 * The agenda of the dashboard: which hairdressers are shown, the slot size, the
 * current time line, and the side panel that opens, books and cancels an
 * appointment. Nothing here reaches the server. A booking or a cancellation
 * lasts until the page reloads, since the agenda is invented for now.
 */

const fill = (sentence, values) =>
    Object.entries(values).reduce((text, [key, value]) => text.replaceAll(`:${key}`, value), sentence);

export const dayAgenda = (config) => ({
    ...config,
    panel: null,
    selected: null,
    draft: null,
    confirming: false,
    added: [],
    removed: [],
    nowMinutes: 0,
    timer: null,

    init() {
        this.tick();
        this.timer = setInterval(() => this.tick(), 30000);
    },

    destroy() {
        clearInterval(this.timer);
    },

    tick() {
        const parts = new Intl.DateTimeFormat('en-GB', {
            timeZone: this.timezone,
            hour: '2-digit',
            minute: '2-digit',
            hourCycle: 'h23',
        }).formatToParts(new Date());

        const part = (type) => parseInt(parts.find((item) => item.type === type).value, 10);

        this.nowMinutes = part('hour') * 60 + part('minute');
    },

    time(minutes) {
        return new Intl.DateTimeFormat(this.locale, { hour: 'numeric', minute: '2-digit', timeZone: 'UTC' })
            .format(new Date(Date.UTC(2000, 0, 1, Math.floor(minutes / 60), minutes % 60)));
    },

    top(minutes) {
        return Math.round((minutes - this.opening) * this.pixelsPerMinute);
    },

    get showsNow() {
        return this.isToday && ! this.isClosed && this.nowMinutes >= this.opening && this.nowMinutes <= this.closing;
    },

    get nowTop() {
        return this.top(this.nowMinutes);
    },

    get nowLabel() {
        return this.time(this.nowMinutes);
    },

    isVisible(id) {
        return this.visible.includes(id);
    },

    allVisible() {
        return Object.keys(this.columns).every((id) => this.isVisible(id));
    },

    toggleAll() {
        this.visible = this.allVisible() ? [] : Object.keys(this.columns);
    },

    appointmentsOf(id) {
        return this.columns[id].appointments
            .filter((appointment) => ! this.removed.includes(appointment.id))
            .concat(this.added.filter((appointment) => appointment.hairdresser === id));
    },

    countLabel(id) {
        return this.countLabels[this.appointmentsOf(id).length] ?? '';
    },

    summary() {
        const total = this.visible.reduce((sum, id) => sum + this.appointmentsOf(id).length, 0);

        return this.summaryLabels[total] ?? '';
    },

    /*
     * The free slots of a column at the chosen size. A slot starting inside an
     * appointment is left out, so the keyboard never books on top of one.
     */
    slots(id) {
        const column = this.columns[id];
        const taken = this.appointmentsOf(id);
        const slots = [];

        column.workingRanges.forEach(([start, end]) => {
            const first = this.opening + Math.ceil((start - this.opening) / this.slot) * this.slot;

            for (let minutes = first; minutes < end; minutes += this.slot) {
                if (taken.some((appointment) => minutes >= appointment.start && minutes < appointment.start + appointment.duration)) {
                    continue;
                }

                slots.push({
                    start: minutes,
                    top: this.top(minutes),
                    height: Math.round(Math.min(this.slot, end - minutes) * this.pixelsPerMinute),
                    label: fill(this.sentences.book, { time: this.time(minutes), name: column.name }),
                });
            }
        });

        return slots;
    },

    addedOf(id) {
        return this.added.filter((appointment) => appointment.hairdresser === id);
    },

    view(appointment) {
        this.selected = appointment;
        this.confirming = false;
        this.panel = 'view';
    },

    book(id, start) {
        this.draft = { hairdresser: id, start, client: '', service: 0 };
        this.panel = 'book';
    },

    bookFirstSlot() {
        const id = this.visible.find((visibleId) => ! this.columns[visibleId].absent)
            ?? Object.keys(this.columns).find((columnId) => ! this.columns[columnId].absent);

        if (id === undefined) {
            return;
        }

        const slots = this.slots(id);
        const after = this.isToday ? this.nowMinutes : this.opening;
        const slot = slots.find((candidate) => candidate.start >= after) ?? slots[0];

        if (slot === undefined) {
            return;
        }

        this.book(id, slot.start);
    },

    get draftTitle() {
        if (this.draft === null) {
            return '';
        }

        return fill(this.sentences.draftTitle, {
            time: this.time(this.draft.start),
            name: this.columns[this.draft.hairdresser].name,
        });
    },

    serviceOption(service) {
        return fill(this.sentences.serviceOption, { service: service.name, minutes: service.duration });
    },

    canAdd() {
        return this.draft !== null && this.draft.client.trim() !== '';
    },

    add() {
        if (! this.canAdd()) {
            return;
        }

        const service = this.services[this.draft.service];
        const start = this.draft.start;
        const end = start + service.duration;
        const height = Math.round(service.duration * this.pixelsPerMinute) - 2;
        const range = fill(this.sentences.range, { start: this.time(start), end: this.time(end) });

        this.added.push({
            id: `added-${Date.now()}`,
            hairdresser: this.draft.hairdresser,
            start,
            duration: service.duration,
            top: this.top(start) + 1,
            height,
            compact: height < 44,
            time: range,
            client: this.draft.client.trim(),
            service: service.name,
            serviceDetail: fill(this.sentences.serviceDetail, { service: service.name, minutes: service.duration }),
            hairdresserName: this.columns[this.draft.hairdresser].name,
            when: fill(this.sentences.when, { range }),
        });

        this.closePanel();
    },

    cancel() {
        if (this.selected.id.startsWith('added-')) {
            this.added = this.added.filter((appointment) => appointment.id !== this.selected.id);
        } else {
            this.removed.push(this.selected.id);
        }

        this.closePanel();
    },

    closePanel() {
        this.panel = null;
        this.selected = null;
        this.draft = null;
        this.confirming = false;
    },
});
