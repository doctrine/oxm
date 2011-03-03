# Doctrine 2 OXM

Doctrine OXM is a PHP 5.3 project for PHP object to XML mapping that provides support for persisting the XML to a file system via common Doctrine techniques.  

Note:  This project is still in it's infancy and should not be used for any production related code.  Its API is still quite unstable and not all features are yet implemented.  Any help in advancing the project forward would be welcome.  Let the forking begin!


## XmlEntityManager

Based on the EntityManager of the Doctrine 2 ORM project.  Objects mapped as an @XmlRootEntity may be managed by the XmlEntityManager.  As these objects are persisted, they are saved in XML format to the file system under a user configured directory and naming scheme.

    $user = new User();
    $user->setId(1);
    $user->setName("Malcolm Reynolds");
    $user->setOccupation("Space Cowboy");

    $em->persist($user);
    $em->flush();            // saves marshalled user object as XML to the file system.


The XmlEntityManager also allows access to repository type objects for loading entities back again.  

    $user = $em->getRepository("User")->findById(1);


## Xml Marshallers

OXM also provides first class support for the Marshalling and Unmarshalling of PHP Objects to XML and vice-versa based on ideas within the Java Castor XML mapping project.  Class metadata for mapped class may be configured via @Annotations, XML, Yaml, or even PHP itself.  Marshallers will uses this class metadata to convert your defined objects to XML, as well as reading in XML and converting them to objects.

Marshallers are utilized by the XmlEntityManager internally, but are also provided to be used as first class citizens within your code for whatever you need.  This makes working with XML in your application so much easier because you don't really have to think about it anymore!

Example use case:

    $xml = $this->getRequest()->getParam('posted_xml');

    $orderObject = $marshaller->unmarshal($xml);
    $this->view->message = "Order recieved:  " . $order->getId();

    $update = new UpdateNotification("Order " . $order->getId() . " was received by the application");
    $xml = $marshaller->marshal($update);

    $this->sendToExternalService($xml);


### API DOCUMENTATION - todo
