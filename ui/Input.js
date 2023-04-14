export class OBInput extends HTMLElement
{
  // forward attributes from the custom element to the ref element
  forwardAttributes(attributes, ref) {
    attributes.forEach((attribute) => {
        if (this.hasAttribute(attribute)) {
            ref.setAttribute(attribute, this.getAttribute(attribute));
        }
    });
  }

  // forward events from the custom element to the ref element
  // is this useful?
  /*
  forwardEvents(events, ref) {
      events.forEach((event) => {
          this.addEventListener(event, (e) => {
              ref.dispatchEvent(new CustomEvent(event, { e }));
          });
      });
  }
  */

  // emit events from the ref element to the custom element
  emitEvents(events, ref) {
    events.forEach((event) => {
      ref.addEventListener(event, (e) => {
        this.dispatchEvent(new CustomEvent(event, { e }));
      });
    });
  }
}