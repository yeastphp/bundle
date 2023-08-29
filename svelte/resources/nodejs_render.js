require("svelte/register")


let stdin = process.stdin;

let buffer = "";

stdin.setEncoding('utf-8');
stdin.on('data', (chunk) => {
  buffer += chunk;

  let nextIndex;
  while ((nextIndex = buffer.indexOf("\n")) !== -1) {
    let cmd = buffer.slice(0, nextIndex);
    buffer = buffer.slice(nextIndex + 1);

    handleCommand(cmd);
  }
})

function handleCommand(data) {

  let result;
  try {
    let cmd = JSON.parse(data);
    const Element = require(cmd.component).default;

    const {head, html, css} = Element.render(cmd.props, {
      context: new Map(cmd.context)
    })

    result = {
      status: "success",
      input: cmd,
      response: {head, html, css}
    }
  } catch (e) {
    result = {
      status: "error",
      error: e.toString(),
    }
  }

  process.stdout.write(JSON.stringify(result) + "\n");
}