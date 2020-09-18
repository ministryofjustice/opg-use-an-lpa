import countdownTimer from "./countdownTimer";

export default class SessionDialog {
    constructor(element, countdownMinutes)
    {
        this.requestHeaders = {"Content-type": "application/json"};

        this.element = element;
        this.dialogOverlay = document.getElementById("dialog-overlay");
        this.dialogFocus = document.querySelector(".dialog-focus");
        this._setupEventHandlers();
        this._trapFocus();

        this.on('tick', timeRemaining => {
            if (timeRemaining >= 200 && timeRemaining <= 300) {
                this._isHidden(false);
            }
        })
    }

    _runInterval()
    {
        const _this = this;

        setInterval(() => {
            _this._requestSessionTimeRemaining()
            .then(sessionData => {
                if (sessionData.session_warning) {
                    _this.emit('tick', sessionData.time_remaining);
                }
            });
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

    async _getSessionTime()
    {
        return fetch("/session-check", this.requestHeaders)
        .then(response => {
            return response.json()
        });
    }

    async _getNewSession()
    {
        await fetch("/session-refresh", this.requestHeaders);
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
