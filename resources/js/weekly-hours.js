/*
 * The weeks of step 4 of the onboarding: the opening hours of the salon, and the
 * working hours of one team member in a side panel. A week is a list of seven
 * days, Monday first, each open or not with one or more ranges written HH:MM.
 * Nothing here is stored; the forms carry the week to the server.
 */

const minutes = (time) => parseInt(time.slice(0, 2), 10) * 60 + parseInt(time.slice(3), 10);

const time = (total) => `${String(Math.floor(total / 60)).padStart(2, '0')}:${String(total % 60).padStart(2, '0')}`;

const label = (value) => `${parseInt(value.slice(0, 2), 10)}${value.slice(2)}`;

const rangeMinutes = (range) => Math.max(0, minutes(range.end) - minutes(range.start));

const sorted = (ranges) => [...ranges].sort((a, b) => minutes(a.start) - minutes(b.start));

const copy = (days) => days.map((day) => ({ ...day, ranges: day.ranges.map((range) => ({ ...range })) }));

/*
 * The message refusing the ranges of a day, or an empty string when they can
 * be stored: a range ends after it starts, and two ranges never overlap.
 */
const dayError = (day, sentences) => {
    if (! day.open) {
        return '';
    }

    if (day.ranges.some((range) => minutes(range.end) <= minutes(range.start))) {
        return sentences.endBeforeStart;
    }

    const ordered = sorted(day.ranges);

    for (let index = 1; index < ordered.length; index++) {
        if (minutes(ordered[index].start) < minutes(ordered[index - 1].end)) {
            return sentences.overlap;
        }
    }

    return '';
};

const dayMinutes = (day) => (day.open ? day.ranges.reduce((sum, range) => sum + rangeMinutes(range), 0) : 0);

const lastEnd = (ranges) => minutes(sorted(ranges).pop()?.end ?? '08:00');

/*
 * A range after the last one of the day, an hour later when the day allows it,
 * right after it otherwise, and never past 22:00, so it never overlaps.
 */
const nextRange = (ranges, length) => {
    const end = lastEnd(ranges);
    const start = Math.max(end, Math.min(end + 60, 21 * 60));

    return { start: time(start), end: time(Math.min(start + length, 22 * 60)) };
};

const week = (config) => ({
    times: config.times,
    sentences: config.sentences,
    hours(total) {
        return new Intl.NumberFormat(config.locale, { maximumFractionDigits: 1 }).format(total / 60);
    },
    summary(days) {
        const open = days.filter((day) => day.open).length;
        const total = days.reduce((sum, day) => sum + dayMinutes(day), 0);

        return this.sentences.summaries[open].replace(':hours', this.hours(total));
    },
    dayHours(day) {
        return this.sentences.dayHours.replace(':hours', this.hours(dayMinutes(day)));
    },
    dayError(day) {
        return dayError(day, this.sentences);
    },
    hasErrors(days) {
        return days.some((day) => dayError(day, this.sentences) !== '');
    },
    canAddRange(day) {
        return lastEnd(day.ranges) < 22 * 60;
    },
    rangeText(range) {
        return this.sentences.range.replace(':start', label(range.start)).replace(':end', label(range.end));
    },
});

export function openingHoursEditor(config) {
    return {
        ...week(config),
        locale: config.locale,
        days: config.days,
        editing: null,
        useOpeningHours: config.useOpeningHours,
        openDayCount() {
            return this.days.filter((day) => day.open).length;
        },
        toggle(index) {
            if (! this.days[index].open && this.editing === index) {
                this.editing = null;
            }
        },
        edit(index) {
            if (! this.days[index].open) {
                return;
            }

            this.editing = this.editing === index ? null : index;
        },
        bars(day) {
            return sorted(day.ranges)
                .filter((range) => minutes(range.end) > minutes(range.start))
                .map((range) => {
                    const length = rangeMinutes(range);
                    const short = (value) => (value.endsWith(':00') ? String(parseInt(value, 10)) : label(value));

                    return {
                        left: `${((minutes(range.start) - 360) / 960) * 100}%`,
                        width: `${(length / 960) * 100}%`,
                        isWide: length >= 300,
                        start: label(range.start),
                        end: label(range.end),
                        compact: `${short(range.start)}–${short(range.end)}`,
                    };
                });
        },
        lunchIndex(day) {
            return day.ranges.findIndex((range) => minutes(range.start) < 12 * 60 && minutes(range.end) > 13 * 60);
        },
        closeForLunch(day) {
            const index = this.lunchIndex(day);
            const range = day.ranges[index];

            day.ranges.splice(index, 1, { start: range.start, end: '12:00' }, { start: '13:00', end: range.end });
        },
        addRange(day) {
            day.ranges.push(nextRange(day.ranges, 60));
        },
        removeRange(day, index) {
            day.ranges.splice(index, 1);
        },
    };
}

/*
 * The panel sits inside the opening hours editor, and every method reading the
 * opening hours takes them as an argument from the template, where `days` is
 * the editor's week.
 */
export function workingHoursPanel(config) {
    return {
        ...week(config),
        member: copy(config.days),
        follows: config.follows,
        start(salonDays) {
            /*
             * Somebody on the opening hours starts from them as they are on the
             * screen, saved or not.
             */
            if (this.follows) {
                this.member = copy(salonDays);
            }
        },
        restart(salonDays) {
            this.member = copy(salonDays);
        },
        salonText(salon) {
            if (! salon.open) {
                return this.sentences.salonClosedShort;
            }

            return this.sentences.salonHours.replace(':hours', sorted(salon.ranges).map((range) => this.rangeText(range)).join(', '));
        },
        warning(day, salon) {
            if (! day.open || this.dayError(day) !== '') {
                return '';
            }

            if (! salon.open) {
                return this.sentences.salonClosed.replace(':day', salon.name);
            }

            const outside = day.ranges.some((range) => ! salon.ranges.some((open) => minutes(range.start) >= minutes(open.start) && minutes(range.end) <= minutes(open.end)));

            return outside ? this.sentences.outsideOpeningHours : '';
        },
        addHours(day) {
            day.ranges.push(nextRange(day.ranges, 120));
        },
    };
}
