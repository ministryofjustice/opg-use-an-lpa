import countdownTimer from './countdownTimer';

describe('disableButtonOnClick', () => {
    jest.useFakeTimers();
    let timer;
    let eventCounter;
    let isCompleted;

    beforeEach(() => {
        document.body.innerHTML = `<span id="time"></span>`;
        eventCounter = 0;
        isCompleted = false;
        timer = new countdownTimer(document.querySelector('#time'), 20);

    });

    describe('without the attribute', () => {
        test('clicking button will not disable it', () => {
            timer.on('tick', (event) => {
                eventCounter = event;
            });
            timer.on('tickCompleted', () => {
                isCompleted = true;
            });

            timer.start()

            jest.advanceTimersByTime(60000);
            expect(eventCounter).toBe(19);
            expect(document.querySelector('#time').innerHTML).toBe('19')
            jest.advanceTimersByTime(120000);
            expect(eventCounter).toBe(17);
            expect(document.querySelector('#time').innerHTML).toBe('17')
            expect(isCompleted).toBeFalsy();
        });
    });

    describe('without the attribute', () => {
        test('clicking button will not disable it', () => {
            timer.on('tick', (event) => {
                eventCounter = event;
            });
            timer.on('tickCompleted', () => {
                isCompleted = true;
            });

            timer.start()

            jest.advanceTimersByTime(120000);
            expect(eventCounter).toBe(18);
            expect(document.querySelector('#time').innerHTML).toBe('18')
            expect(isCompleted).toBeFalsy();
            timer.reset(20)
            jest.advanceTimersByTime(1200000);
            expect(eventCounter).toBe(0);
            expect(document.querySelector('#time').innerHTML).toBe('0')
            expect(isCompleted).toBeTruthy();
        });
    });
});
