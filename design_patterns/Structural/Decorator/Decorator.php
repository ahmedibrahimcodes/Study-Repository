<?php

// idea: add dynamic behavior by wrap object

// works as layers, object inside object inside object.. Each layer (decorator) assigns extra behaviors to object at runtime

// decorator doesnt add or change to the origin object (component) public interface .. 
// the original object decorator stays with same public methods, but its just adding extra behaviour then call the parent

// typical decorator is not just only one decorator, typically would be multiple decorators and you would combine them 
// however you like in runtime to add behavior dynamically.. if its just one decorator class then it is better to be called
// another design pattern, maybe proxy or something else


//- Component: the original object to be wrapped
//- Decorator: wrapper


// decorator is useful when you have combinations of behavior and you would choose behavior to add in runtime..

// suppose you have coffee which have cost and description .. pretty straight forward
// now you want to sell types of these cofees MilkCoffee , SugarCoffee, VaniliaCofee ..till this point inheritance will do the job
// now there is combinations chosen in runTime .. MilkSugarCoffee or MilkVaniliaCofee or ValidaSugarCoffee etc.. here decorator excel



// Component Interface
interface Coffee {
    public function getCost(): float;
    public function getDescription(): string;
}

// Component
class SimpleCoffee implements Coffee {
    public function getCost(): float {
        return 2.00;
    }
    
    public function getDescription(): string {
        return "Simple Coffee";
    }
}


// Base Decorator
abstract class CoffeeDecorator implements Coffee {
    protected Coffee $coffee;
    
    public function __construct(Coffee $coffee) {
        $this->coffee = $coffee;
    }
    
    public function getCost(): float {
        return $this->coffee->getCost();
    }
    
    public function getDescription(): string {
        return $this->coffee->getDescription();
    }
}


// Concrete Decorators
class MilkDecorator extends CoffeeDecorator {
    public function getCost(): float {
        return $this->coffee->getCost() + 0.50;
    }
    
    public function getDescription(): string {
        return $this->coffee->getDescription() . ", Milk";
    }
}

class SugarDecorator extends CoffeeDecorator {
    public function getCost(): float {
        return $this->coffee->getCost() + 0.20;
    }
    
    public function getDescription(): string {
        return $this->coffee->getDescription() . ", Sugar";
    }
}

class WhippedCreamDecorator extends CoffeeDecorator {
    public function getCost(): float {
        return $this->coffee->getCost() + 0.70;
    }
    
    public function getDescription(): string {
        return $this->coffee->getDescription() . ", Whipped Cream";
    }
}

class VanillaDecorator extends CoffeeDecorator {
    public function getCost(): float {
        return $this->coffee->getCost() + 0.60;
    }
    
    public function getDescription(): string {
        return $this->coffee->getDescription() . ", Vanilla";
    }
}


// Usage Example
echo "=== Coffee Shop Order System ===\n\n";

// Order 1: Simple Coffee
$coffee1 = new SimpleCoffee();
echo "Order 1: " . $coffee1->getDescription() . "\n";
echo "Cost: $" . number_format($coffee1->getCost(), 2) . "\n\n";

// Order 2: Coffee with Milk
$coffee2 = new MilkDecorator(new SimpleCoffee());
echo "Order 2: " . $coffee2->getDescription() . "\n";
echo "Cost: $" . number_format($coffee2->getCost(), 2) . "\n\n";

// Order 3: Coffee with Milk and Sugar
$coffee3 = new SugarDecorator(
    new MilkDecorator(
        new SimpleCoffee()
    )
);


//Q: Decorator seems complex in its structure, should we use it?
// well, if you can use more simpler way like composite pattern then do it, but if you really need decorator then you have to
class CompositeCoffee implements Coffee{
    private $coffees = [];
    public function __construct(array $coffees)
    {
        $this->coffees = $coffees;
    }

    public function getCost():float{
        $c = 0;
        foreach($this->coffees as $coffee){
            $c += $coffee->getCost();
        }
        return $c;
    }

    public function getDescription():string{
        $d = 0;
        foreach($this->coffees as $coffee){
            $d .= $coffee->getDescription();
        }
        return $d;
    }
}

$c = new CompositeCoffee([new SimpleCoffee,new MilkCoffee, new VaniliaCofee]);
$c->getCost();







//Q: Decorator works in layers, object inside object inside object, same as COR.. so what is the difference?

// Decorator
// ALL layers execute and contribute to Add/enhance functionality
// Think: "Apply all these transformations"


// Chain of Responsibility (CoR)
// ONE handler from chain processes and typically stops .. Find the right handler for the request
// Think: "Who can handle this?"


// They can co-exist like in laravel middlewares
// Chain of Responsibility
// Pass a request through a chain of handlers where each handler can decide to:
// Handle the request, OR Pass it to the next handler.

// Decorator Pattern
// transform response object to add headers, compress output, or log content 
// each decorator adds extra behavior while still being “a response.”