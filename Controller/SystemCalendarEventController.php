<?php

namespace Oro\Bundle\CalendarBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;

use Oro\Bundle\CalendarBundle\Entity\SystemCalendar;
use Oro\Bundle\CalendarBundle\Entity\CalendarEvent;
use Oro\Bundle\CalendarBundle\Provider\SystemCalendarConfig;

class SystemCalendarEventController extends Controller
{
    /**
     * @Route("/event/view/{id}", name="oro_system_calendar_event_view", requirements={"id"="\d+"})
     * @Template
     */
    public function viewAction(CalendarEvent $entity)
    {
        $calendar = $entity->getSystemCalendar();
        if (!$calendar) {
            // an event must belong to system calendar
            throw $this->createNotFoundException('Not system calendar event.');
        }

        $this->checkPermissionByConfig($calendar);

        if (!$calendar->isPublic() && !$this->isGranted('VIEW', $calendar)) {
            // an user must have permissions to view system calendar
            throw new AccessDeniedException();
        }

        $isEventManagementGranted = $calendar->isPublic()
            ? $this->isGranted('oro_public_calendar_management')
            : $this->isGranted('oro_system_calendar_management');

        return [
            'entity'    => $entity,
            'editable'  => $isEventManagementGranted,
            'removable' => $isEventManagementGranted
        ];
    }

    /**
     * @Route("/{id}/event/create", name="oro_system_calendar_event_create", requirements={"id"="\d+"})
     * @Template("OroCalendarBundle:SystemCalendarEvent:update.html.twig")
     * @param Request $request
     * @param SystemCalendar $calendar
     * @return array|RedirectResponse
     */
    public function createAction(Request $request, SystemCalendar $calendar)
    {
        $this->checkPermissionByConfig($calendar);

        $isGranted = $calendar->isPublic()
            ? $this->isGranted('oro_public_calendar_management')
            : $this->isGranted('oro_system_calendar_management');
        if (!$isGranted) {
            throw new AccessDeniedException();
        }

        $entity = new CalendarEvent();

        $startTime = new \DateTime('now', new \DateTimeZone('UTC'));
        $endTime   = new \DateTime('now', new \DateTimeZone('UTC'));
        $endTime->add(new \DateInterval('PT1H'));
        $entity->setStart($startTime);
        $entity->setEnd($endTime);
        $entity->setSystemCalendar($calendar);

        return $this->update(
            $request,
            $entity,
            $this->get('router')->generate('oro_system_calendar_event_create', ['id' => $calendar->getId()])
        );
    }

    /**
     * @Route("/event/update/{id}", name="oro_system_calendar_event_update", requirements={"id"="\d+"})
     * @Template
     * @param Request $request
     * @param CalendarEvent $entity
     * @return array|RedirectResponse
     */
    public function updateAction(Request $request, CalendarEvent $entity)
    {
        $calendar = $entity->getSystemCalendar();
        if (!$calendar) {
            // an event must belong to system calendar
            throw $this->createNotFoundException('Not system calendar event.');
        }

        $this->checkPermissionByConfig($calendar);

        if (!$calendar->isPublic() && !$this->isGranted('VIEW', $calendar)) {
            // an user must have permissions to view system calendar
            throw new AccessDeniedException();
        }

        $isGranted = $calendar->isPublic()
            ? $this->isGranted('oro_public_calendar_management')
            : $this->isGranted('oro_system_calendar_management');
        if (!$isGranted) {
            throw new AccessDeniedException();
        }

        return $this->update(
            $request,
            $entity,
            $this->get('router')->generate('oro_system_calendar_event_update', ['id' => $entity->getId()])
        );
    }

    /**
     * @param Request $request
     * @param CalendarEvent $entity
     * @param string $formAction
     *
     * @return array
     */
    protected function update(Request $request, CalendarEvent $entity, $formAction)
    {
        $saved = false;

        if ($this->get('oro_calendar.system_calendar_event.form.handler')->process($entity)) {
            if (!$request->get('_widgetContainer')) {
                $this->get('session')->getFlashBag()->add(
                    'success',
                    $this->get('translator')->trans('oro.calendar.controller.event.saved.message')
                );

                return $this->get('oro_ui.router')->redirect($entity);
            }
            $saved = true;
        }

        return [
            'entity'     => $entity,
            'saved'      => $saved,
            'form'       => $this->get('oro_calendar.calendar_event.form.handler')->getForm()->createView(),
            'formAction' => $formAction
        ];
    }

    /**
     * @param SystemCalendar $entity
     *
     * @throws NotFoundHttpException
     */
    protected function checkPermissionByConfig(SystemCalendar $entity)
    {
        if ($entity->isPublic()) {
            if (!$this->getCalendarConfig()->isPublicCalendarEnabled()) {
                throw $this->createNotFoundException('Public calendars are disabled.');
            }
        } else {
            if (!$this->getCalendarConfig()->isSystemCalendarEnabled()) {
                throw $this->createNotFoundException('System calendars are disabled.');
            }
        }
    }

    /**
     * @return SystemCalendarConfig
     */
    protected function getCalendarConfig()
    {
        return $this->get('oro_calendar.system_calendar_config');
    }
}
