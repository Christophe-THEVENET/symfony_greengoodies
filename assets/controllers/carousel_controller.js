import { Controller } from "@hotwired/stimulus";

export default class extends Controller {
    static targets = ["track", "slide"];
    static values = { interval: { type: Number, default: 4000 } };

    connect() {
        this._slides = Array.from(this.trackTarget.children);
        this._total = this._slides.length;
        this._visible = this._computeVisible();
        this._cloneSlides();
        this._slideW = 0;
        this._gap = 24;
        this._resize();
        this._index = this._visible;
        var self = this;
        setTimeout(function () {
            self._resize();
            self._go(false);
            self._startAuto();
        }, 150);
        this._onResize = this._resize.bind(this);
        window.addEventListener("resize", this._onResize);
    }

    disconnect() {
        this._stopAuto();
        window.removeEventListener("resize", this._onResize);
    }

    _computeVisible() {
        var w = window.innerWidth;
        if (w < 680) return 1;
        if (w < 980) return 2;
        return 3;
    }

    _cloneSlides() {
        var track = this.trackTarget;
        var children = Array.from(track.children);
        var n = this._visible;
        for (var i = children.length - 1; i >= children.length - n; i--) {
            track.insertBefore(children[i].cloneNode(true), track.firstChild);
        }
        for (var i = 0; i < n; i++) {
            track.appendChild(children[i].cloneNode(true));
        }
        this._slides = Array.from(track.children);
    }

    _resize() {
        this._visible = this._computeVisible();
        this._gap = this._visible === 1 ? 0 : 24;
        this.trackTarget.style.gap = this._gap + "px";
        var w = this.element.offsetWidth;
        if (w <= 0) w = this.element.parentElement.offsetWidth;
        if (w <= 0) return;
        this._slideW = (w - (this._visible - 1) * this._gap) / this._visible;
        this._slides.forEach(function (s) {
            s.style.flex = "0 0 " + this._slideW + "px";
        }, this);
    }

    _startAuto() { this._timer = setInterval(() => this.next(), this.intervalValue); }
    _stopAuto() { clearInterval(this._timer); }

    next() {
        if (this._busy) return;
        this._stopAuto();
        this._index++;
        this._go(true);
        this._startAuto();
    }

    prev() {
        if (this._busy) return;
        this._stopAuto();
        this._index--;
        this._go(true);
        this._startAuto();
    }

    _go(animate) {
        if (this._slideW <= 0) return;
        this._busy = animate;
        this.trackTarget.style.transition = animate
            ? "transform 0.5s cubic-bezier(0.16, 1, 0.3, 1)"
            : "none";
        this.trackTarget.style.transform = "translateX(-" + this._index * (this._slideW + this._gap) + "px)";

        if (animate) {
            var self = this;
            setTimeout(function () {
                self._busy = false;
                if (self._index < self._visible) {
                    self._index += self._total;
                    self._go(false);
                } else if (self._index >= self._visible + self._total) {
                    self._index -= self._total;
                    self._go(false);
                }
            }, 520);
        }
    }
}
