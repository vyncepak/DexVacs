      document.addEventListener("DOMContentLoaded", function () {
        /* ---- Mobile drawer ---- */
        var drawer = document.getElementById("drawer");
        var backdrop = document.getElementById("backdrop");
        function setDrawer(open) {
          drawer.classList.toggle("open", open);
          backdrop.classList.toggle("active", open);
          document.body.style.overflow = open ? "hidden" : "";
        }
        document
          .getElementById("menuBtn")
          .addEventListener("click", function () {
            setDrawer(true);
          });
        document
          .getElementById("drawerClose")
          .addEventListener("click", function () {
            setDrawer(false);
          });
        backdrop.addEventListener("click", function () {
          setDrawer(false);
        });
        drawer.querySelectorAll(".drawer-nav a").forEach(function (a) {
          a.addEventListener("click", function () {
            setDrawer(false);
          });
        });
        document.addEventListener("keydown", function (e) {
          if (e.key === "Escape") setDrawer(false);
        });

        /* ---- OldwebUI tabs ---- */
        document.querySelectorAll(".r-tabs").forEach(function (nav) {
          var tabs = nav.querySelectorAll(":scope > a");
          tabs.forEach(function (tab) {
            tab.addEventListener("click", function (e) {
              e.preventDefault();
              tabs.forEach(function (t) {
                var on = t === tab;
                t.setAttribute("aria-selected", on ? "true" : "false");
                var panel = document.querySelector(t.getAttribute("href"));
                if (panel) panel.hidden = !on;
              });
            });
          });
        });

        /* ---- Modal ---- */
        var dlg = document.getElementById("thanksDialog");
        dlg.querySelectorAll("[data-close]").forEach(function (b) {
          b.addEventListener("click", function () {
            dlg.close();
          });
        });
        dlg.addEventListener("click", function (e) {
          var r = dlg.getBoundingClientRect();
          if (
            e.clientY < r.top ||
            e.clientY > r.bottom ||
            e.clientX < r.left ||
            e.clientX > r.right
          )
            dlg.close();
        });
        document
          .getElementById("contactForm")
          .addEventListener("submit", function (e) {
            e.preventDefault();
            dlg.showModal();
            this.reset();
          });

        /* ---- FAQ search ---- */
        var faqs = document.querySelectorAll("#faqList details");
        var empty = document.getElementById("faq-empty");
        document
          .getElementById("faqSearch")
          .addEventListener("input", function () {
            var q = this.value.trim().toLowerCase(),
              shown = 0;
            faqs.forEach(function (d) {
              var hit = !q || d.textContent.toLowerCase().indexOf(q) > -1;
              d.style.display = hit ? "" : "none";
              if (hit) shown++;
            });
            empty.style.display = shown ? "none" : "flex";
          });

        /* ---- Animated counters ---- */
        var counters = document.querySelectorAll("[data-count]");
        var reduce = window.matchMedia(
          "(prefers-reduced-motion: reduce)",
        ).matches;
        function fmt(n, dec) {
          return n.toLocaleString("en-US", {
            minimumFractionDigits: dec,
            maximumFractionDigits: dec,
          });
        }
        function run(el) {
          var target = parseFloat(el.dataset.count),
            dec = parseInt(el.dataset.decimals || "0", 10),
            suf = el.dataset.suffix || "";
          if (reduce) {
            el.textContent = fmt(target, dec) + suf;
            return;
          }
          var t0 = performance.now(),
            dur = 1400;
          (function tick(t) {
            var p = Math.min((t - t0) / dur, 1),
              eased = 1 - Math.pow(1 - p, 3);
            el.textContent = fmt(target * eased, dec) + suf;
            if (p < 1) requestAnimationFrame(tick);
          })(t0);
        }
        if ("IntersectionObserver" in window) {
          var io = new IntersectionObserver(
            function (entries) {
              entries.forEach(function (en) {
                if (en.isIntersecting) {
                  run(en.target);
                  io.unobserve(en.target);
                }
              });
            },
            { threshold: 0.4 },
          );
          counters.forEach(function (c) {
            io.observe(c);
          });
        } else {
          counters.forEach(run);
        }

        /* ---- Scroll: progress bar, back-to-top, active nav ---- */
        var bar = document.getElementById("scrollbar");
        var toTop = document.getElementById("toTop");
        var links = document.querySelectorAll(".nav-links a");
        var secs = Array.prototype.map.call(links, function (a) {
          return document.querySelector(a.getAttribute("href"));
        });
        function onScroll() {
          var h = document.documentElement;
          var max = h.scrollHeight - h.clientHeight;
          bar.style.width = (max > 0 ? (h.scrollTop / max) * 100 : 0) + "%";
          toTop.classList.toggle("show", h.scrollTop > 600);
          var y = h.scrollTop + 120,
            cur = 0;
          secs.forEach(function (s, i) {
            if (s && s.offsetTop <= y) cur = i;
          });
          links.forEach(function (a, i) {
            a.classList.toggle("active", i === cur);
          });
        }
        window.addEventListener("scroll", onScroll, { passive: true });
        onScroll();
      });
    