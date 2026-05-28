<script>
    (function () {
        if (window.self === window.top) {
            return;
        }

        try {
            window.top.location.replace(window.location.href);
        } catch (e) {
            window.location.replace(window.location.href);
        }
    }());
</script>