import countdownTimer from "./countdownTimer";

export default class SessionDialog {
    constructor(element, countdownMinutes)
    {
        this.showDialogMinutesBeforeLogout = 5;
        // this.countdownMinutes = countdownMinutes; // Temporary until final solution in place


        this.element = element;
        this.dialogOverlay = document.getElementById("dialog-overlay");
        this.dialogFocus = document.querySelector(".dialog-focus");
        this._setupEventHandlers();
        this._trapFocus();

        this.on('tick', (timeRemaining) => {
            if (timeRemaining >= 200 && timeRemaining <= 300)
            {
                this._isHidden(false);
            }
        })
        // this.timer = new countdownTimer(this.element.querySelector('#time'), this.countdownMinutes);
        // this.timer.on('tick', (event) => {
        //     if (event === this.showDialogMinutesBeforeLogout) {
        //         this._isHidden(false);
        //     }
        // });
        // this.timer.on('tickCompleted', () => {
        //     window.location.href = '/timeout';
        // });
        //
        // this.timer.start()
    }

    _runInterval()
    {
        const _this = this;

        setInterval(function () {
            const sessionData = _this._requestSessionTimeRemaining();

            if (sessionData.session_warning) {
                if (sessionData.time_remaining > 0) {
                    _this.emit('tick', sessionData.time_remaining);
                }
            }
        }, 60000);
    }

    _setupEventHandlers()
    {
        const _this = this;

        const showTimeoutElements = document.querySelectorAll('.jsShowTimeout');
        for (let i = 0; i < showTimeoutElements.length; i++) {
            showTimeoutElements[i].addEventListener("click", function () {
                _this._isHidden(false);
                _this.dialogFocus.focus();
            });
        }

        const hideTimeoutElements = document.querySelectorAll('.jsHideTimeout');
        for (let i = 0; i < hideTimeoutElements.length; i++) {
            hideTimeoutElements[i].addEventListener("click", function () {
                _this._isHidden(true);
                _this._refreshSession();
            });
        }
    }

    async _requestSessionTimeRemaining()
    {
        const request = { headers: {"Content-type": "application/json"}};
        const response = await fetch("/session-check", request);
        const data = await response.json()

        if (data.session_warning !== true) {
            return false;
        }

        return data;
    }

    async _refreshSession()
    {
        const request = { headers: {"Content-type": "application/json"}};
        return await fetch("/session-refresh", request);
    }

    _isHidden(isVisible)
    {
        this.element.classList.toggle("hide", isVisible);
        this.element.classList.toggle("dialog", !isVisible);
        this.dialogOverlay.classList.toggle("hide", isVisible);
        this.dialogOverlay.classList.toggle("dialog-overlay", !isVisible);
    }

    _trapFocus()
    {
        const focusableEls = this.element.querySelectorAll('a[href]:not([disabled]), button:not([disabled]), textarea:not([disabled]), input[type="text"]:not([disabled]), input[type="radio"]:not([disabled]), input[type="checkbox"]:not([disabled]), select:not([disabled])');
        const firstFocusableEl = focusableEls[0];
        const lastFocusableEl = focusableEls[focusableEls.length - 1];
        const KEY_CODE_TAB = 9;
        const KEY_CODE_ESC = 27;
        const _this = this;

        // Track Tab event and trap the focus to only elements within the dialog
        // Also track ESC key for closing the window
        this.element.addEventListener('keydown', function (e) {
            const isTabPressed = (e.key === 'Tab' || e.keyCode === KEY_CODE_TAB);
            const isEscPressed = (e.key === 'Esc' || e.keyCode === KEY_CODE_ESC);

            if (!isTabPressed && !isEscPressed) {
                return;
            }

            if (isTabPressed) {
                /* istanbul ignore next */
                if ( e.shiftKey ) { /* shift + tab */
                    if (document.activeElement === firstFocusableEl) {
                        lastFocusableEl.focus();
                        e.preventDefault();
                    }
                } else /* tab */ {
                    if (document.activeElement === lastFocusableEl) {
                        firstFocusableEl.focus();
                        e.preventDefault();
                    }
                }
            }

            if (isEscPressed) {
                _this._isHidden(true);
            }
        });
    }
};
