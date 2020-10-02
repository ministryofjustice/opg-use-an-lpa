export default class SessionDialog {
    constructor(element)
    {
        this.requestHeaders = { "Content-type": "application/json" };
        this.element = element;
        this.dialogOverlay = document.querySelector("#dialog-overlay");
        this.dialogFocus = document.querySelector(".dialog-focus");
        this._setupEventHandlers();
        this._trapFocus();
        this._runInterval();
    }

    async checkSessionExpires()
    {
        const sessionData = await this._getSessionTime();

        if (sessionData.session_warning && sessionData.time_remaining > 1 && sessionData.time_remaining <= 300) {
            this._isHidden(false);
            return;
        } else {
            return;
        }
    }

    async _runInterval()
    {
        const _this = this;
        /* istanbul ignore next */
        setInterval(async function () {
            return _this.checkSessionExpires();
        }, 60000);
    }

    _hideDialog()
    {
        this._isHidden(true);
        this._getNewSession();
    }

    _setupEventHandlers()
    {
        const _this = this;

        const hideTimeoutElements = document.querySelectorAll('.jsHideTimeout');
        for (let i = 0; i < hideTimeoutElements.length; i++) {
            hideTimeoutElements[i].addEventListener("click", function () {
                _this._hideDialog();
            });
        }
    }

    async _getSessionTime()
    {
        const response = await fetch("/session-check", this.requestHeaders)
        console.log(response);
        if (response.redirected) {
            document.location.href = "/session-expired";
        }
        else {
            return response.json()
        }
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
        if (!isVisible) {
            this.dialogFocus.focus();
        }
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
                if (e.shiftKey) { /* shift + tab */
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
                _this._hideDialog();
            }
        });
    }
};
