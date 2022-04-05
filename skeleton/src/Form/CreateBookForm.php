<?php
namespace EfTech\BookLibrary\Form;


use EfTech\BookLibrary\Service\SearchAuthorsService;
use EfTech\BookLibrary\Service\SearchAuthorsService\SearchAuthorsCriteria;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\Extension\Core\Type as FormElement;
use Symfony\Component\Form\CallbackTransformer;
use Symfony\Component\Validator\Constraints as Assert;

/**
 *  Реализация настройки формы, описывающая создание книги
 */
class CreateBookForm extends AbstractType
{
    /** Сервис поиска авторов
     * @var SearchAuthorsService
     */
    private SearchAuthorsService $authorsService;

    /**
     * @param SearchAuthorsService $authorsService
     */
    public function __construct(SearchAuthorsService $authorsService)
    {
        $this->authorsService = $authorsService;
    }


    /**
     * @throws \JsonException
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder->add('title', FormElement\TextType::class, [
            'required' => true,
            'label' => 'Название',
            'priority' => 400,
            'constraints' => [
                new Assert\Type([
                    'type' => 'string',
                    'message' => 'Данные о заголовке должны быть строкой'
                ]),
                new Assert\NotBlank([
                    'normalizer' => 'trim',
                    'message' => 'заголовок не может быть пустым'
                ]),
                new Assert\Length([
                    'min' => 1,
                    'max' => 255,
                    'minMessage' => 'Минимальная длина заголовка должна быть не меньше одного символа',
                    'maxMessage' => 'заголовок не может быть длинее {{limit}} символов'
                ])
            ]
        ])->add('year', FormElement\IntegerType::class, [
            'required' => true,
            'label' => 'Год издания',
            'priority' => 300,
            'constraints' => [
                    new Assert\Type(['type' => 'int', 'message' => 'Год издания книги должен быть числом']),
                    new Assert\Positive(
                        ['message' => 'Год издания книги не может быть меньше или равным 0']
                    ),
                    new Assert\Range([
                        'max' => (int)date('Y'),
                        'maxMessage' => 'Год не может быть больше {{limit}} символов'
                    ])
                ]
        ])->add('author_id_list',FormElement\ChoiceType::class,[
            'required' => true,
            'multiple' => true,
            'label' => 'Авторы',
            'choices' => $this->authorsService->search(new SearchAuthorsCriteria()),
            'choice_label' => static function(SearchAuthorsService\AuthorDto $dtoAuthor):string {
                return $dtoAuthor->getName() . ' ' . $dtoAuthor->getSurname();
            },
            'choice_value' => static function(SearchAuthorsService\AuthorDto $dtoAuthor):string {
                return $dtoAuthor->getId();
            },
            'priority' => 200
        ])->add('submit', FormElement\SubmitType::class, [
            'label' => 'Добавить',
            'priority' => 100
        ])->setMethod('POST');

        $builder->get('author_id_list')->addModelTransformer(new CallbackTransformer(
            static function( $authorIdList) {
                return $authorIdList;
            },
            static function( $authorIdList) {
                return array_map(static function(SearchAuthorsService\AuthorDto $authorDto) {
                    return $authorDto->getId();
                },$authorIdList);
            }
        ));

        parent::buildForm($builder, $options);
    }

}