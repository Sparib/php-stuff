let run = () => {
    let list = document.getElementById("todo_list");
    if (list == null) return;
    let mutList = list.cloneNode();
    let req = new XMLHttpRequest();
    req.onreadystatechange = () => {
        if (req.readyState == 4 && req.status == 200) {
            resp = JSON.parse(req.responseText);
            Array.from(mutList.children).forEach(e => e.remove());
            console.log(resp);
            if (resp.success == true) {
                for (const [k, v] of Object.entries(resp)) {
                    if (k == "success") continue;
                    let complete = typeof(v) == "boolean" && v;

                    let item = document.createElement("li");

                    let span = document.createElement("span");
                    span.classList.add("fa-li");
                    let i = document.createElement("i");
                    if (!complete) i.classList.add("fa-solid", "fa-square-xmark");
                    else i.classList.add("fa-solid", "fa-square-check");
                    span.appendChild(i);
                    item.appendChild(span);

                    let text = document.createTextNode(k);
                    if (complete) {
                        let strike = document.createElement("strike");
                        strike.appendChild(text);
                        item.appendChild(strike);
                    } else item.appendChild(text);
                    mutList.appendChild(item);
                }
            }
            list.replaceWith(mutList);
        }
    };

    req.open("GET", `${location.protocol}//api.${location.hostname}/get/todo`);
    req.send();
}
run();