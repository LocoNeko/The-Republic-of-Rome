Most up-to-date description of POST handling :

- The < body> has a **data-json** attribute that holds only one value upon loading : **{user_id : _the user id_}**
- Each **card < div>** (they have the 'sprite' class) has a **data-json** attribute as well
- All global elements that need to be passed through POST must have the **global-postable** class
- Buttons have a **verb** attribute
- Upon clicking the button, the following happens :
 - The json from **data-json** is put into a **json** variable
 - If the button was on a card the json from the card's **data-json** is added to the json , otherwise all **global-postable** data is added to json
 - **{verb : _the verb from the button_}** is added to the json
 - the socket.io event is emitted
 - The POST is called, with the **json** as data on the route **[current route] / [verb]**

Refactoring :
Remove the attributes of the card div from Card Presenter and Card_new.twig
---

Refactor all < forms > and their elements so they behave like that :

< form > classes :
- a static **json-submit** class, used to trigger the actual submit

< form > attributes :
- a variable **user_id**, used to store the user_id fo the player submitting the form
- a variable **phase**
- a variable **verb**, used to know the end point to use in the form {phase}/{verb}

< button type="submit" > class :
- **submitWithVerb** triggers the update of the Form's verb

< div > attributes for cards :
- **treasury** : The current treasury (if this is a senator)
- **card_name** : The name of the card
- **card_id** : The id of the card (warning : for Senators, this is NOT the SenatorID)

Type of events :
- Clicking a button
- Dropping a draggable
- Clicking a button on a slider

Type of elements :
- Submit buttons
- Show slider
- Draggable card or icon
- Dropable card or icon
- Menu on a card

## Actions
All actions are linked to a verb attribute (same name as the action) that is submitted with the form. The actions below all require some kind of information, "DONE" or "READY" actions are omitted.

### setupPickLeader
- A text **element** that says 'Leader'
- An icon **element**, could be called "decoration"
- A **draggable** div containing both elements
- All senators of this party are **droppable**

### setupPlayStatesman
- A **menu** for all playable Statesmen (only for cards in hand)
- (optional) : Instead of the menu, draw an icon for unplaybale statesmen, with a pop up explaining the reason

### setupPlayConcession
- All playable concessions in hand are **draggable**
- All senators of this party are **droppable**

### revenueDone
- A form with all information is submitted

### revenueRedistribute
- Text **elements** with names of each party except the current one which is simply named "your party"
- Icon **elements**, could be called "decoration"
- **droppable** div containing both elements with a **user_id** attribute
- The div of the current player is also **draggable**
- All senators of this party are **droppable**
- All senators of this party are **draggable**
- A **modal slider** appears upon dropping

### revenueGiveToRome
- A **menu** for all senators with treasury>0 (only for senators in the party)
- (optional) : Instead of the menu, draw an icon for senators with 0 treasury

### persuasionPickTarget
- A form with all information is submitted
- (optional) a **modal slider** could appear upon clicking "Persuade", it could include an odds calculator

### persuasionCounterBribe
- A **modal slider** appears upon clicking "Counter Bribe", it could include an odds calculator

### persuasionBribeMore
- A form with all information is submitted
_**OR**_
- (optional) a **modal slider** could appear upon clicking "Persuade", it could include an odds calculator

### forumAttractKnight
