# H
H is a smooth HTML generator for PHP, optionally validation aware and autofixing

## Features
- Clean, IDE-friendly syntax using named arguments. Essential html syntax/nesting is enforced through php syntax
- Any html tag (including customs), any attribute
- Shorter syntax than html, cleaner than any other html generation method. No more html concatenation or mixed syntax !
- No dependencies
- Thread-safe
- Shines with custom components ! (see 'more examples' at the end)
- Proper handling of:
  - Boolean attributes
  - Void elements
  - Nested elements
  - Array classes
  - Object conversion
- Three ouptut modes:
  - RAW: (Default) Generate HTML without restrictions. No formatting.
  - SMART: Auto-fix invalid HTML like webkit browsers do. Auto Identing and smart break-lines.
  - STRICT: Throw exceptions for HTML violations

## Installation
```bash
composer require jengo\H
```

## quickstart
```php
use Jengo\H;
use Jengo\HE; // For direct echo

// Simple usage
echo H::div('Hello World', class: 'greeting');

// Or using the echo shortcut
HE::div('Hello World', class: 'greeting');
```
will echo
```html
<div class="greeting">Hello World</div>
```

Complex structure with smart mode
```php
H::setMode(H::MODE_SMART);
echo H::div(
    H::h1('Title', class: ['main', 'bold']),
    H::p(
        'Content with a ',
        H::a('link', href: '#'),
        ' and some ',
        H::strong('bold text')
    ),
    class: 'container'
);
```

will echo
```html
<div class="container">
  <h1 class="main bold">Title</h1>
  <p>
    Content with a <a href="#">link</a> and some <strong>bold text</strong>
  </p>
</div>
```
More examples at the bottom.

## Syntax
Content first, attributes last - that's all!

### Content can be:
	•	Text: H::p('Hello')
	•	Nodes: H::div(H::span('text'))
	•	Arrays: H::ul(['item1', 'item2'])
	•	Mixed: H::div('text', H::p('para'), ['item1', 'item2'])
	•	Objects with __toString: H::div($anyObjectWithToString)
 
### Attributes:
	•	Named arguments: H::div('content', class: 'main')
	•	Boolean attributes: H::input(type: 'checkbox', required: true)
	•	Array classes: H::div('content', class: ['btn', 'btn-primary'])

### Modes
*RAW* (Default)
H::setMode(H::MODE_RAW);
  - Fastest performance
  - No validation
  - No formatting

*SMART*
H::setMode(H::MODE_SMART);
  -	Auto-fixes like webkit browsers. (eg. nested links)  
  -	Pretty printing
  -	Handles nested elements intelligently

*STRICT*
H::setMode(H::MODE_STRICT);
 -	Throws exceptions for HTML violations
 -	Great for development


## Debug Mode
```php
use Jengo\HED; // For debug output (shows both code and result)
// Debug view (shows both code and result)
HED::div('Hello World', class: 'greeting');
```

## Document generation
```php
HED::document(
    H::head(
        H::meta(charset: 'UTF-8'),
        H::title('My Page'),
        H::link(rel: 'stylesheet', href: 'style.css')
    ),
    H::body(
        H::div(
            H::h1('Card Title', class: 'card-title'),
            H::img(src: 'card.jpg', class: 'card-img'),
            H::div(
                H::p('Card text'),
                H::a('Read more', href: '#', class: ['btn', 'btn-primary']),
                class: 'card-body'
            ),
            class: 'card'
        ),
        class: 'container'
    )
);
```
//will echo
```html
<!DOCTYPE html>
<html>
  <head>
    <meta charset="UTF-8" />
    <title>My Page</title>
    <link rel="stylesheet" href="style.css" />
  </head>
  <body class="container">
    <div class="card">
      <h1 class="card-title">Card Title</h1>
      <img src="card.jpg" class="card-img" />
      <div class="card-body">
        <p>Card text</p>
        <a href="#" class="btn btn-primary">Read more</a>
      </div>
    </div>
  </body>
</html>
```

## Performance
- In raw mode, you bascially don't have to worry about performance. it easilly generate documents with thousands of nodes in less than 50ms
- In smart mode, it will begin to be noticable for document generation more than XXX nodes.
  
## Limitations
- Not much. You can't avoid a syntax error if you're using it wrong, like H::p(class:'main', 'text content') ; it will produce a XX Exception. See *Syntax*
- (IN PROGRESS) Indentation and break-line are not perfect in smart mode
- strict mode can generate invalid html. Only surface checks are done, there is no formal html validation, and there probably won't be.
  - Eg:
- THe DOM tree is only conserved through call stack, so no virtual dom is built. So, totally threadsafe. However, the $mode is a static var, so will be shared amon all your html generation. 
- If you need 
  

## Other Examples

### Custom Components
```php
function Card(string $title, string $content, array $classes = []): string {
    return H::div(
        H::div(H::h5($title), class: 'card-header'),
        H::div(H::p($content), class: 'card-body'),
        class: array_merge(['card'], $classes)
    );
}

echo Card('Welcome', 'Hello World', ['mt-3']);
```
will display
```html
<div class="container">
  <div class="card mb-3">
    <div class="card-header">
      <h5> First Card </h5>
    </div>
    <div class="card-body">
      <p> Some content </p>
    </div>
    <div class="card-footer"> Footer text </div>
  </div>
  <div class="card mb-3 border-primary">
    <div class="card-header">
      <h5> Second Card </h5>
    </div>
    <div class="card-body">
      <p> Other content </p>
    </div>
  </div>
</div>
```
### Table generation
```php
HE::h2('4 Table with dynamic content:');

$data = [
    ['id' => 1, 'name' => 'John', 'role' => 'Admin'],
    ['id' => 2, 'name' => 'Jane', 'role' => 'User'],
];

HE::table(
    H::thead(
        H::tr(
            H::th('ID'),
            H::th('Name'),
            H::th('Role'),
            H::th('Actions')
        )
    ),
    H::tbody(
        array_map(
            fn($row) =>
            H::tr(
                H::td($row['id']),
                H::td($row['name']),
                H::td($row['role']),
                H::td(
                    H::a('Edit', href: "#edit-{$row['id']}", class: ['btn', 'btn-sm', 'btn-primary']),
                    H::button('Delete', class: ['btn', 'btn-sm', 'btn-danger'])
                )
            ),
            $data
        )
    ),
    class: ['table', 'table-striped']
);
```
will produce
```html
<table class="table table-striped">
  <thead>
    <tr>
      <th> ID </th>
      <th> Name </th>
      <th> Role </th>
      <th> Actions </th>
    </tr>
  </thead>
  <tbody>
    <tr>
      <td> 1 </td>
      <td> John </td>
      <td> Admin </td>
      <td>
        <a href="#edit-1" class="btn btn-sm btn-primary">Edit</a>
        <button class="btn btn-sm btn-danger">Delete</button>
      </td>
    </tr>
    <tr>
      <td> 2 </td>
      <td> Jane </td>
      <td> User </td>
      <td>
        <a href="#edit-2" class="btn btn-sm btn-primary">Edit</a>
        <button class="btn btn-sm btn-danger">Delete</button>
      </td>
    </tr>
  </tbody>
</table>
```


## Contributing
Contributions are welcome! Please feel free to submit a Pull Request.

## License
MIT License - see the LICENSE file for details
