function toggleMenu() {
    var n = document.getElementById("nav");
    if ("nav" === n.className) {
        n.className += " responsive";
    } else {
        n.className = "nav";
    }
}
