import Vue from 'vue'
import Vuelidate from 'vuelidate'
Vue.use(Vuelidate)

import {mount} from '@vue/test-utils'
import ForgotPassword from '../components/auth/ForgotPassword.vue'

describe('Forgot password tests for valid parameters', () => {
    it('Call the sendEmailToResetPassword function on form submit', () => {
        const wrapper = mount(ForgotPassword)
        const sendEmailToResetPassword = jest.fn()
        wrapper.setMethods({sendEmailToResetPassword})
        wrapper.find('form').trigger('submit')
        expect(sendEmailToResetPassword).toHaveBeenCalled()
    })

    it('Forgot password proceed to send email for password reset when email input is valid', async () => {
        const wrapper = mount(ForgotPassword)
        const validation = wrapper.vm.$v.email
        wrapper.find('#email').setValue('test@mail.com')
        const sendEmailToResetPassword = jest.fn()
        wrapper.setMethods({sendEmailToResetPassword})
        wrapper.find('form').trigger('submit')
        await Vue.nextTick()
        expect(validation.$invalid).toBe(false)
    })

    it('Required and email validation rule should be true on form submit when email input is valid', () => {
        const wrapper = mount(ForgotPassword)
        const validation = wrapper.vm.$v.email
        wrapper.find('#email').setValue('test@mail.com')
        const sendEmailToResetPassword = jest.fn()
        wrapper.setMethods({sendEmailToResetPassword})
        wrapper.find('form').trigger('submit')
        expect(validation.required).toBe(true)
    })

    it('Required and email validation rule should be true on typing when email input is valid', () => {
        const wrapper = mount(ForgotPassword)
        const validation = wrapper.vm.$v.email
        wrapper.find('#email').trigger('keydown.up')
        wrapper.find('#email').setValue('test@mail.com')
        expect(validation.required).toBe(true)
    })
})
