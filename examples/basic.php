<?php

use Jengo\H;
use Jengo\HE;
use Jengo\HED;

//Samples
H::H2("simple texxt");

H::H2("full document");
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

HE::H2('1.	Mode-specific behavior and validation:');

// Strict mode
H::setMode(H::MODE_STRICT);
//try {
HED::DIV('This will throw - uppercase tag');
HED::div(H::p('This will throw - p cannot contain div', H::div('nested')));
HED::a(H::a('This will throw - nested a tags'), href: '#');
//} catch (Exception $e) {
//    echo "Strict mode caught: " . $e->getMessage() . "\n";
//}

// Smart mode
H::setMode(H::MODE_SMART);
HED::div(
    H::p('This p will be auto-closed', H::div('when div starts')),
    H::a(H::a('Inner link auto-closes outer', href: '#2'), href: '#1')
);

// Loose mode
H::setMode(H::MODE_LOOSE);
HED::div(
    H::p('Anything goes here', H::div('nested div in p is fine')),
    H::a(H::a('Nested links are fine too', href: '#2'), href: '#1')
);
HE::H2("2.	Boolean attributes and array handling:");

HED::div(
    // Boolean attributes
    H::input(type: 'checkbox', checked: true, disabled: false),
    H::button('Submit', disabled: true),

    // Array classes
    H::div('Content', class: ['container', 'mt-4', 'border']),

    // Array content
    ['Item 1', 'Item 2', 'Item 3'],

    // Mixed content
    [
        H::span('Inline'),
        'Plain text',
        new class {
            public function __toString() {
                return 'Object text';
            }
        }
    ]
);
HE::H2("3.	Complex form example:");

HED::form(
    H::div(
        H::label('Username:', for: 'username'),
        H::input(
            type: 'text',
            id: 'username',
            required: true,
            class: ['form-control', 'mb-3']
        ),
        class: 'form-group'
    ),
    H::div(
        H::label('Options:', for: 'options'),
        H::select(
            H::option('Option 1', value: '1', selected: true),
            H::option('Option 2', value: '2'),
            H::option('Option 3', value: '3', disabled: true),
            id: 'options',
            class: 'form-select'
        ),
        class: 'form-group'
    ),
    H::div(
        H::input(type: 'checkbox', id: 'terms', required: true),
        H::label('I agree to terms', for: 'terms'),
        class: 'form-check'
    ),
    H::button('Submit', type: 'submit', class: ['btn', 'btn-primary']),
    method: 'post',
    class: 'needs-validation'
);
HE::H2('4 Table with dynamic content:');

$data = [
    ['id' => 1, 'name' => 'John', 'role' => 'Admin'],
    ['id' => 2, 'name' => 'Jane', 'role' => 'User'],
];

HED::table(
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
HE::H2('5.	Custom components pattern:');

function Card(string $title, string $content, ?string $footer = null, array $classes = []): string {
    return H::div(
        H::div(H::h5($title), class: 'card-header'),
        H::div(H::p($content), class: 'card-body'),
        $footer ? H::div($footer, class: 'card-footer') : null,
        class: array_merge(['card'], $classes)
    );
}

HED::div(
    Card('First Card', 'Some content', 'Footer text', ['mb-3']),
    Card('Second Card', 'Other content', null, ['mb-3', 'border-primary']),
    class: 'container'
);
