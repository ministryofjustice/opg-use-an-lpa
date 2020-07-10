const EventEmitter = require('events');

export default class CountdownTimer extends EventEmitter {
    constructor(element, counter)
    {
        super();
        this.element = element;
        this.counter = counter; // Temporary until final solution in place
    }

    _countZero()
    {
        if (this.counter === 0) {
            this.emit('tickCompleted');
        }
    }

    start()
    {
        const _this = this;
        setInterval(function () {
            if (_this.counter > 0) {
                _this.counter--;
            }
            _this._countZero();
            _this.element.innerHTML = _this.counter.toString();
            _this.emit('tick', _this.counter);
        }, 60000);
    }

    reset(countdownMinutes)
    {
        this.counter = countdownMinutes;
    }
}
